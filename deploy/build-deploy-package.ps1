<#
.SYNOPSIS
  Package changed files into a zip for upload via cPanel File Manager,
  without touching vendor/.

.DESCRIPTION
  Two modes, controlled by -Uncommitted:

  Committed mode (default): diffs git history between the last-recorded
  deploy marker (deploy/.last-deployed) and HEAD. Only files that have
  actually been committed are included.

  Uncommitted mode (-Uncommitted): packages whatever `git status` currently
  shows - modified tracked files AND new untracked files - regardless of
  whether it's been committed. Use this if you stage your "ready to deploy"
  files as uncommitted/untracked work rather than committing first.

  Either way: files that don't belong on the server (tests/, phpunit.xml,
  etc.) are excluded, and a plain-text manifest is written listing, for
  every packaged file, its local (Windows) path and its destination
  (cPanel/Linux, forward-slash) path relative to the app root - handy for
  placing files by hand if you're not extracting the whole zip in one go.

  Deleted files are NOT removed by extracting the zip in File Manager -
  the script prints them separately (and in the manifest) so you can
  remove them by hand.

.EXAMPLE
  # Committed mode, first run ever - tell it what commit/tag production is on
  .\deploy\build-deploy-package.ps1 -Since <commit-or-tag>

.EXAMPLE
  # Committed mode, every run after that - uses the saved marker automatically
  .\deploy\build-deploy-package.ps1

.EXAMPLE
  # Uncommitted mode - package whatever's currently modified/untracked
  .\deploy\build-deploy-package.ps1 -Uncommitted

.EXAMPLE
  # Prefix destination paths in the manifest with the real server path
  .\deploy\build-deploy-package.ps1 -Uncommitted -ServerRoot '/home/ahan/public_html/portal.abiacommunitymanchester.org.uk'
#>

param(
    [string]$Since,
    [string]$ServerRoot,
    [switch]$Uncommitted
)

$ErrorActionPreference = 'Stop'

$repoRoot = (git rev-parse --show-toplevel).Trim()
Set-Location $repoRoot

$deployDir  = Join-Path $repoRoot 'deploy'
$markerFile = Join-Path $deployDir '.last-deployed'

if (-not (Test-Path $deployDir)) {
    New-Item -ItemType Directory -Path $deployDir | Out-Null
}

# Paths that are dev-only tooling and never belong on the server
$excludePrefixes = @(
    'tests/',
    'phpunit.xml',
    '.phpunit.result.cache',
    '.gitignore',
    'deploy/',
    'README.md'
)

function Test-Excluded([string]$path) {
    foreach ($prefix in $excludePrefixes) {
        if ($path.StartsWith($prefix)) { return $true }
    }
    return $false
}

$toCopy  = New-Object System.Collections.Generic.List[string]
$deleted = New-Object System.Collections.Generic.List[string]
$rangeLabel = $null
$sinceHash  = $null
$headHash   = $null

if ($Uncommitted) {
    if ($Since) {
        Write-Warning "-Since is ignored in -Uncommitted mode."
    }

    # -uall so new files in new directories are listed individually, not
    # collapsed to a single directory entry.
    $statusLines = git status --porcelain=v1 -uall

    foreach ($line in $statusLines) {
        if (-not $line.Trim()) { continue }

        $indexStatus    = $line[0]
        $worktreeStatus = $line[1]
        $rest           = $line.Substring(3)

        if ($indexStatus -eq 'R' -or $worktreeStatus -eq 'R') {
            # Rename: "old -> new" (paths with spaces/special chars aren't handled)
            $arrowParts = $rest -split ' -> '
            $oldPath = $arrowParts[0]
            $newPath = $arrowParts[1]
            if (-not (Test-Excluded $oldPath)) { $deleted.Add($oldPath) }
            if (-not (Test-Excluded $newPath)) { $toCopy.Add($newPath) }
            continue
        }

        $path = $rest
        if (Test-Excluded $path) { continue }

        if ($indexStatus -eq 'D' -or $worktreeStatus -eq 'D') {
            $deleted.Add($path)
        } else {
            # Covers ?? (untracked), M/A/C in either column
            $toCopy.Add($path)
        }
    }

    $rangeLabel = "working tree (uncommitted + untracked changes)"

} else {
    if (-not $Since) {
        if (Test-Path $markerFile) {
            $Since = (Get-Content $markerFile -Raw).Trim()
        } else {
            Write-Error "No deploy marker found at deploy\.last-deployed. Run once with -Since <commit-or-tag> to tell it what's currently live on the server, or use -Uncommitted to package working-tree changes instead."
            exit 1
        }
    }

    # Resolve to a real commit hash so the marker we save is unambiguous
    $sinceHash = (git rev-parse $Since 2>$null)
    if (-not $sinceHash) {
        Write-Error "Couldn't resolve '$Since' to a commit. Check the ref/tag/hash and try again."
        exit 1
    }
    $headHash = (git rev-parse HEAD).Trim()

    if ($sinceHash -eq $headHash) {
        Write-Host "Nothing committed since $($sinceHash.Substring(0,7)) - already up to date."
        exit 0
    }

    $diffLines = git diff --name-status $sinceHash $headHash

    foreach ($line in $diffLines) {
        if (-not $line.Trim()) { continue }
        $parts  = $line -split "`t"
        $status = $parts[0]

        if ($status.StartsWith('R')) {
            $oldPath = $parts[1]
            $newPath = $parts[2]
            if (-not (Test-Excluded $oldPath)) { $deleted.Add($oldPath) }
            if (-not (Test-Excluded $newPath)) { $toCopy.Add($newPath) }
            continue
        }

        $path = $parts[1]
        if (Test-Excluded $path) { continue }

        if ($status.StartsWith('D')) {
            $deleted.Add($path)
        } else {
            $toCopy.Add($path)
        }
    }

    $rangeLabel = "$($sinceHash.Substring(0,7))..$($headHash.Substring(0,7))"
}

if ($toCopy.Count -eq 0 -and $deleted.Count -eq 0) {
    Write-Host "No deployable file changes found ($rangeLabel) - only excluded/dev files changed, or nothing changed at all."
    exit 0
}

$timestamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$stageDir  = Join-Path $deployDir "staging-$timestamp"
$zipPath   = Join-Path $deployDir "deploy-$timestamp.zip"

New-Item -ItemType Directory -Path $stageDir | Out-Null

foreach ($file in $toCopy) {
    $src = Join-Path $repoRoot $file
    if (-not (Test-Path $src)) { continue }  # e.g. added then deleted again within the range
    $dest = Join-Path $stageDir $file
    New-Item -ItemType Directory -Path (Split-Path $dest) -Force | Out-Null
    Copy-Item $src $dest -Force
}

Compress-Archive -Path (Join-Path $stageDir '*') -DestinationPath $zipPath -Force
Remove-Item $stageDir -Recurse -Force

# Destination paths from git are already forward-slash (Linux/cPanel-style) regardless
# of host OS - no conversion needed. ServerRoot is optional; without it, destinations
# are shown relative to the app root, which is all File Manager navigation needs.
$rootPrefix = if ($ServerRoot) { $ServerRoot.TrimEnd('/') + '/' } else { '' }

$manifestPath = Join-Path $deployDir "deploy-$timestamp-manifest.txt"
$manifestLines = New-Object System.Collections.Generic.List[string]

$manifestLines.Add("Deploy manifest - generated $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')")
$manifestLines.Add("Source: $rangeLabel")
$manifestLines.Add("")
$manifestLines.Add("=== Files to upload/overwrite ($($toCopy.Count)) ===")
$manifestLines.Add("Local path`tDestination path (cPanel)")

foreach ($file in $toCopy) {
    $localPath = Join-Path $repoRoot ($file -replace '/', [System.IO.Path]::DirectorySeparatorChar)
    $destPath  = $rootPrefix + $file
    $manifestLines.Add("$localPath`t$destPath")
}

if ($deleted.Count -gt 0) {
    $manifestLines.Add("")
    $manifestLines.Add("=== Delete on server - NOT removed by extracting the zip ($($deleted.Count)) ===")
    $manifestLines.Add("Destination path (cPanel)")
    foreach ($file in $deleted) {
        $manifestLines.Add($rootPrefix + $file)
    }
}

Set-Content -Path $manifestPath -Value $manifestLines -Encoding UTF8

Write-Host ""
Write-Host "Deploy package ready: $zipPath" -ForegroundColor Green
Write-Host "Manifest: $manifestPath" -ForegroundColor Green
Write-Host "  $($toCopy.Count) file(s) packaged ($rangeLabel)"

if ($deleted.Count -gt 0) {
    Write-Host ""
    Write-Host "MANUAL ACTION - these won't be removed by extracting the zip:" -ForegroundColor Yellow
    $deleted | ForEach-Object { Write-Host "  - $_" -ForegroundColor Yellow }
}

Write-Host ""
Write-Host "Next steps:"
Write-Host "  1. Upload $($zipPath | Split-Path -Leaf) via cPanel File Manager"
Write-Host "  2. Extract it over your app root (this merges/overwrites, doesn't wipe untouched files)"
Write-Host "     - or use $($manifestPath | Split-Path -Leaf) to place files one by one instead"
if ($deleted.Count -gt 0) {
    Write-Host "  3. Manually delete the file(s) listed above"
}
Write-Host "  4. In cPanel Terminal: php artisan migrate --force (if there are new migrations)"
Write-Host "  5. php artisan optimize:clear && php artisan config:cache && php artisan view:cache"
Write-Host ""

if ($Uncommitted) {
    Write-Host "Uncommitted mode - nothing to mark. Once you've confirmed this is live, commit these" -ForegroundColor Cyan
    Write-Host "files locally so committed-mode runs later have a clean baseline to diff from." -ForegroundColor Cyan
} else {
    $confirm = Read-Host "Once you've confirmed the upload is live, mark $($headHash.Substring(0,7)) as deployed? (y/N)"
    if ($confirm -eq 'y') {
        Set-Content -Path $markerFile -Value $headHash -NoNewline
        Write-Host "Marked $($headHash.Substring(0,7)) as the last deployed commit." -ForegroundColor Green
    } else {
        Write-Host "Not marked. Re-run this script anytime to rebuild the same package, or once you've confirmed it's live:"
        Write-Host "  '$headHash' | Out-File -FilePath '$markerFile' -NoNewline"
    }
}
