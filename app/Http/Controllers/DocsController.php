<?php

namespace App\Http\Controllers;

use App\Support\Docs\DocsCatalog;
use Illuminate\Support\Collection;

class DocsController extends Controller
{
    /** Land on the first article the current user can see. */
    public function index()
    {
        $flat = DocsCatalog::flatten(DocsCatalog::filteredFor(auth()->user()));

        abort_if($flat === [], 404, 'No documentation is available for your account yet.');

        $first = $flat[0];

        return redirect()->route('docs.show', [$first['namespace'], $first['section'], $first['article']]);
    }

    public function show(string $namespace, string $section, string $article)
    {
        $flat = DocsCatalog::flatten(DocsCatalog::filteredFor(auth()->user()));

        $index = Collection::make($flat)->search(
            fn ($a) => $a['namespace'] === $namespace && $a['section'] === $section && $a['article'] === $article
        );

        abort_if($index === false, 404);

        $current = $flat[$index];

        return view('docs.layout', [
            'nav'         => DocsCatalog::filteredFor(auth()->user()),
            'current'     => $current,
            'prev'        => $flat[$index - 1] ?? null,
            'next'        => $flat[$index + 1] ?? null,
            'contentView' => $current['view'],
        ]);
    }

    /**
     * Live search index, JSON, filtered to whatever the current user is
     * allowed to see — generated on the fly from DocsCatalog rather than a
     * static build artifact, so it's never out of sync with access rules.
     */
    public function searchIndex()
    {
        $flat = DocsCatalog::flatten(DocsCatalog::filteredFor(auth()->user()));

        $index = Collection::make($flat)->map(fn ($a) => [
            'title'        => $a['title'],
            'sectionLabel' => $a['sectionLabel'],
            'excerpt'      => $a['excerpt'],
            'keywords'     => implode(' ', $a['keywords']),
            'url'          => route('docs.show', [$a['namespace'], $a['section'], $a['article']]),
        ])->values();

        return response()->json($index);
    }
}
