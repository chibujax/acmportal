<?php

namespace App\Support\Docs;

use App\Models\User;

/**
 * Single source of truth for the docs site's structure: which sections and
 * articles exist, which Blade view renders each one, and who's allowed to
 * see it. Both the docs controller (nav + access checks) and the live
 * search index read from this one place, so there's nothing to keep in sync
 * separately when an article is added.
 *
 * Access model:
 * - The 'member' namespace is visible to every authenticated user.
 * - The 'admin' namespace is visible only to admins, and a section/article
 *   inside it can optionally require a specific page-access slug (see
 *   Role::$availablePages) via a 'page' key — same slug a scoped admin role
 *   (e.g. "Treasurer") would need to see the matching feature itself.
 *   No 'page' key means "any admin can see this."
 */
class DocsCatalog
{
    public static function all(): array
    {
        return [
            'member' => [
                'label' => 'Member Guide',
                'icon'  => 'bi-house-heart',
                'sections' => [
                    'getting-started' => [
                        'label' => 'Getting Started',
                        'articles' => [
                            'signing-in' => [
                                'title'    => 'Signing In',
                                'view'     => 'docs.member.getting-started.signing-in',
                                'keywords' => ['login', 'sign in', 'password'],
                                'excerpt'  => 'How to sign in to the portal with your phone or email.',
                            ],
                            'joining-the-portal' => [
                                'title'    => 'Joining the Portal',
                                'view'     => 'docs.member.getting-started.joining-the-portal',
                                'keywords' => ['join', 'register', 'sign up', 'otp', 'verification code'],
                                'excerpt'  => 'Verifying your identity and creating your login if an admin has already added you.',
                            ],
                            'activating-your-account' => [
                                'title'    => 'Activating Your Account',
                                'view'     => 'docs.member.getting-started.activating-your-account',
                                'keywords' => ['activate', 'invite', 'set password'],
                                'excerpt'  => 'Setting your first password from an activation link or invite.',
                            ],
                            'forgot-password' => [
                                'title'    => 'Forgot Password',
                                'view'     => 'docs.member.getting-started.forgot-password',
                                'keywords' => ['reset password', 'forgot', 'otp', 'locked out'],
                                'excerpt'  => 'Resetting your password by email link or SMS code.',
                            ],
                            'verifying-your-email' => [
                                'title'    => 'Verifying Your Email',
                                'view'     => 'docs.member.getting-started.verifying-your-email',
                                'keywords' => ['email verification', 'verify email', 'resend'],
                                'excerpt'  => 'Confirming your email address and resending the verification link.',
                            ],
                        ],
                    ],
                    'your-dashboard' => [
                        'label' => 'Your Dashboard',
                        'articles' => [
                            'dashboard-overview' => [
                                'title'    => 'Your Dashboard',
                                'view'     => 'docs.member.your-dashboard.dashboard-overview',
                                'keywords' => ['dashboard', 'home', 'outstanding', 'overview'],
                                'excerpt'  => 'Reading your dashboard: what you\'ve paid, what you owe, and what needs your attention.',
                            ],
                        ],
                    ],
                    'paying-your-dues' => [
                        'label' => 'Paying Your Dues',
                        'articles' => [
                            'making-a-pledge' => [
                                'title'    => 'Making a Pledge',
                                'view'     => 'docs.member.paying-your-dues.making-a-pledge',
                                'keywords' => ['pledge', 'donation', 'free will offering'],
                                'excerpt'  => 'Recording a free-will pledge or donation from your dashboard.',
                            ],
                            'paying-online-with-stripe' => [
                                'title'    => 'Paying Online with Stripe',
                                'view'     => 'docs.member.paying-your-dues.paying-online-with-stripe',
                                'keywords' => ['pay', 'card payment', 'stripe', 'dues'],
                                'excerpt'  => 'Paying dues online by card, in full or in part.',
                            ],
                            'payment-history' => [
                                'title'    => 'Payment History',
                                'view'     => 'docs.member.paying-your-dues.payment-history',
                                'keywords' => ['payment history', 'receipts', 'past payments'],
                                'excerpt'  => 'Viewing your full record of past payments and receipts.',
                            ],
                        ],
                    ],
                    'profile-and-family' => [
                        'label' => 'Your Profile & Family',
                        'articles' => [
                            'my-profile' => [
                                'title'    => 'My Profile',
                                'view'     => 'docs.member.profile-and-family.my-profile',
                                'keywords' => ['profile', 'edit details', 'address', 'occupation'],
                                'excerpt'  => 'Viewing and editing your contact details.',
                            ],
                            'spouse-link-unlink' => [
                                'title'    => 'Linking or Unlinking a Spouse',
                                'view'     => 'docs.member.profile-and-family.spouse-link-unlink',
                                'keywords' => ['spouse', 'marriage', 'couple dues', 'family'],
                                'excerpt'  => 'Linking your spouse so dues are calculated as a shared household.',
                            ],
                            'managing-children' => [
                                'title'    => 'Managing Children',
                                'view'     => 'docs.member.profile-and-family.managing-children',
                                'keywords' => ['children', 'family records', 'kids'],
                                'excerpt'  => 'Adding and removing your children\'s family records.',
                            ],
                        ],
                    ],
                    'meetings' => [
                        'label' => 'Meetings',
                        'articles' => [
                            'qr-checkin' => [
                                'title'    => 'QR & Location Check-In',
                                'view'     => 'docs.member.meetings.qr-checkin',
                                'keywords' => ['check in', 'qr code', 'attendance', 'location'],
                                'excerpt'  => 'Checking in to a meeting by scanning the QR code.',
                            ],
                            'my-attendance' => [
                                'title'    => 'My Attendance',
                                'view'     => 'docs.member.meetings.my-attendance',
                                'keywords' => ['attendance record', 'attendance rate', 'missed meetings'],
                                'excerpt'  => 'Reviewing your attendance record and rate for the year.',
                            ],
                            'meeting-minutes' => [
                                'title'    => 'Meeting Minutes',
                                'view'     => 'docs.member.meetings.meeting-minutes',
                                'keywords' => ['minutes', 'meeting notes', 'pdf'],
                                'excerpt'  => 'Viewing and downloading published meeting minutes.',
                            ],
                        ],
                    ],
                ],
            ],
            'admin' => [
                'label'     => 'Admin Guide',
                'icon'      => 'bi-shield-lock',
                'adminOnly' => true,
                'sections' => [
                    'member-management' => [
                        'label' => 'Member Management',
                        'page'  => 'members',
                        'articles' => [
                            'member-directory' => [
                                'title'    => 'Member Directory & Detail',
                                'view'     => 'docs.admin.member-management.member-directory',
                                'keywords' => ['members', 'search member', 'member detail', 'find member'],
                                'excerpt'  => 'Searching, filtering, and viewing an individual member record.',
                            ],
                            'csv-import-invites' => [
                                'title'    => 'CSV Import & Invitations',
                                'page'     => 'import',
                                'view'     => 'docs.admin.member-management.csv-import-invites',
                                'keywords' => ['import members', 'csv', 'invite', 'bulk add', 'spreadsheet'],
                                'excerpt'  => 'Bulk-adding members from a spreadsheet and sending registration invites.',
                            ],
                        ],
                    ],
                    'dues-and-payments' => [
                        'label' => 'Dues & Payments',
                        'page'  => 'payments',
                        'articles' => [
                            'dues-cycle-creation' => [
                                'title'    => 'Creating a Dues Cycle',
                                'view'     => 'docs.admin.dues-and-payments.dues-cycle-creation',
                                'keywords' => ['dues cycle', 'levy', 'create cycle', 'yearly dues'],
                                'excerpt'  => 'Setting up a new yearly dues cycle, event levy, or donation campaign.',
                            ],
                            'pledges-and-donation-items' => [
                                'title'    => 'Pledges & Donation Items',
                                'view'     => 'docs.admin.dues-and-payments.pledges-and-donation-items',
                                'keywords' => ['pledges', 'donation items', 'in-kind', 'anonymous donor'],
                                'excerpt'  => 'Recording money pledges and in-kind item donations for a pledge-based cycle.',
                            ],
                            'manual-payments' => [
                                'title'    => 'Recording Manual Payments',
                                'view'     => 'docs.admin.dues-and-payments.manual-payments',
                                'keywords' => ['manual payment', 'cash', 'bank transfer', 'record payment'],
                                'excerpt'  => 'Recording a cash or bank transfer payment on a member\'s behalf.',
                            ],
                            'stripe-reconciliation' => [
                                'title'    => 'Stripe Reconciliation',
                                'page'     => 'reconciliation',
                                'view'     => 'docs.admin.dues-and-payments.stripe-reconciliation',
                                'keywords' => ['reconciliation', 'stripe', 'bank statement', 'match payments'],
                                'excerpt'  => 'Matching online card payments against what actually lands in the bank.',
                            ],
                        ],
                    ],
                    'meetings-and-attendance' => [
                        'label' => 'Meetings & Attendance',
                        'page'  => 'meetings',
                        'articles' => [
                            'meeting-creation-qr' => [
                                'title'    => 'Creating a Meeting & QR Check-In',
                                'view'     => 'docs.admin.meetings-and-attendance.meeting-creation-qr',
                                'keywords' => ['create meeting', 'qr code', 'activate meeting', 'location radius'],
                                'excerpt'  => 'Scheduling a meeting, setting its location, and activating QR check-in.',
                            ],
                            'attendance-report' => [
                                'title'    => 'Attendance Report',
                                'page'     => 'attendance',
                                'view'     => 'docs.admin.meetings-and-attendance.attendance-report',
                                'keywords' => ['attendance report', 'attendance rate', 'eligibility'],
                                'excerpt'  => 'Yearly attendance statistics per member.',
                            ],
                            'consecutive-absentees' => [
                                'title'    => 'Consecutive Absentees',
                                'page'     => 'absentees',
                                'view'     => 'docs.admin.meetings-and-attendance.consecutive-absentees',
                                'keywords' => ['absentees', 'missed meetings', 'follow up'],
                                'excerpt'  => 'Finding members who\'ve missed several meetings in a row.',
                            ],
                            'meeting-minutes-admin' => [
                                'title'    => 'Publishing Meeting Minutes',
                                'page'     => 'minutes',
                                'view'     => 'docs.admin.meetings-and-attendance.meeting-minutes-admin',
                                'keywords' => ['minutes', 'upload minutes', 'publish'],
                                'excerpt'  => 'Uploading and publishing meeting minutes for members to see.',
                            ],
                        ],
                    ],
                    'reports' => [
                        'label' => 'Reports',
                        'articles' => [
                            'financial-report' => [
                                'title'    => 'Financial Report',
                                'page'     => 'reports',
                                'view'     => 'docs.admin.reports.financial-report',
                                'keywords' => ['financial report', 'collections', 'by cycle'],
                                'excerpt'  => 'Financial collections broken down by year, cycle, or legacy carryover.',
                            ],
                            'arrears-report' => [
                                'title'    => 'Arrears Report',
                                'page'     => 'arrears',
                                'view'     => 'docs.admin.reports.arrears-report',
                                'keywords' => ['arrears', 'outstanding dues', 'who owes'],
                                'excerpt'  => 'Members with outstanding dues.',
                            ],
                            'engagement-report' => [
                                'title'    => 'Member Engagement Report',
                                'page'     => 'engagement',
                                'view'     => 'docs.admin.reports.engagement-report',
                                'keywords' => ['engagement', 'inactive members', 'last attended', 'last paid'],
                                'excerpt'  => 'Each member\'s last meeting attended and last dues payment.',
                            ],
                            'member-summary-report' => [
                                'title'    => 'Member Summary Report',
                                'page'     => 'reports',
                                'view'     => 'docs.admin.reports.member-summary-report',
                                'keywords' => ['member summary', 'member counts', 'status breakdown'],
                                'excerpt'  => 'A count breakdown of members by status and role.',
                            ],
                        ],
                    ],
                    'communications' => [
                        'label' => 'Communications',
                        'articles' => [
                            'message-templates' => [
                                'title'    => 'SMS & Email Templates',
                                'page'     => 'communications',
                                'view'     => 'docs.admin.communications.message-templates',
                                'keywords' => ['templates', 'sms template', 'email template'],
                                'excerpt'  => 'Creating reusable message templates for reminders and announcements.',
                            ],
                            'bulk-messaging' => [
                                'title'    => 'Bulk Messaging',
                                'page'     => 'messaging',
                                'view'     => 'docs.admin.communications.bulk-messaging',
                                'keywords' => ['bulk message', 'send sms', 'send email', 'announcement'],
                                'excerpt'  => 'Sending an SMS or email to a group of members at once.',
                            ],
                            'contact-log' => [
                                'title'    => 'Contact Log',
                                'page'     => 'messaging',
                                'view'     => 'docs.admin.communications.contact-log',
                                'keywords' => ['contact log', 'message history', 'sent messages'],
                                'excerpt'  => 'A record of every message sent, individually or in bulk.',
                            ],
                            'notification-recipients' => [
                                'title'    => 'System Alert Recipients',
                                'page'     => 'recipients',
                                'view'     => 'docs.admin.communications.notification-recipients',
                                'keywords' => ['alert recipients', 'cron alerts', 'system notifications'],
                                'excerpt'  => 'Who gets notified about automated system alerts (e.g. failed scheduled jobs).',
                            ],
                        ],
                    ],
                    'family-records' => [
                        'label' => 'Family Records',
                        'page'  => 'children',
                        'articles' => [
                            'children-admin' => [
                                'title'    => 'Children & Family Records',
                                'view'     => 'docs.admin.family-records.children-admin',
                                'keywords' => ['children', 'family records admin'],
                                'excerpt'  => 'Viewing and managing children records across all families.',
                            ],
                        ],
                    ],
                    'roles-and-permissions' => [
                        'label' => 'Roles & Permissions',
                        // Real Role Management is Super-Admin-only in the app (no
                        // page slug can delegate it — see Role::$availablePages),
                        // so it's gated the same way here rather than via 'page'.
                        'superAdminOnly' => true,
                        'articles' => [
                            'roles-and-permissions' => [
                                'title'    => 'Roles & Permissions',
                                'view'     => 'docs.admin.roles-and-permissions.roles-and-permissions',
                                'keywords' => ['roles', 'permissions', 'treasurer', 'scoped admin'],
                                'excerpt'  => 'Creating scoped admin roles and assigning them (Super Admin only).',
                            ],
                        ],
                    ],
                    'audit-trail' => [
                        'label' => 'Audit Trail',
                        'page'  => 'audit',
                        'articles' => [
                            'audit-trail' => [
                                'title'    => 'Audit Trail',
                                'view'     => 'docs.admin.audit-trail.audit-trail',
                                'keywords' => ['audit trail', 'activity log', 'who did what'],
                                'excerpt'  => 'Browsing a record of who did what across the portal.',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /** Filter the full catalogue down to what $user is allowed to see. */
    public static function filteredFor(User $user): array
    {
        $filtered = [];

        foreach (static::all() as $namespaceSlug => $namespace) {
            if (($namespace['adminOnly'] ?? false) && ! $user->isAdmin()) {
                continue;
            }

            $sections = [];
            foreach ($namespace['sections'] as $sectionSlug => $section) {
                if (! static::userCanSee($user, $namespace, $section)) {
                    continue;
                }

                $articles = [];
                foreach ($section['articles'] as $articleSlug => $article) {
                    if (! static::userCanSee($user, $namespace, $article)) {
                        continue;
                    }
                    $articles[$articleSlug] = $article;
                }

                if ($articles) {
                    // array_merge, not '+' — the section already has an 'articles' key
                    // (the full unfiltered list), and '+' keeps the left-hand value on
                    // a key collision, which would silently undo this filtering.
                    $sections[$sectionSlug] = array_merge($section, ['articles' => $articles]);
                }
            }

            if ($sections) {
                $filtered[$namespaceSlug] = array_merge($namespace, ['sections' => $sections]);
            }
        }

        return $filtered;
    }

    private static function userCanSee(User $user, array $namespace, array $node): bool
    {
        if (! ($namespace['adminOnly'] ?? false)) {
            return true;
        }

        if ($node['superAdminOnly'] ?? false) {
            return $user->isSuperAdmin();
        }

        $page = $node['page'] ?? null;

        return $page === null || $user->hasAccess($page);
    }

    /**
     * Flatten a (already filtered) catalogue into an ordered list — used for
     * prev/next navigation and to build the search index.
     */
    public static function flatten(array $filtered): array
    {
        $flat = [];

        foreach ($filtered as $namespaceSlug => $namespace) {
            foreach ($namespace['sections'] as $sectionSlug => $section) {
                foreach ($section['articles'] as $articleSlug => $article) {
                    $flat[] = [
                        'namespace'      => $namespaceSlug,
                        'namespaceLabel' => $namespace['label'],
                        'section'        => $sectionSlug,
                        'sectionLabel'   => $section['label'],
                        'article'        => $articleSlug,
                        'title'          => $article['title'],
                        'view'           => $article['view'],
                        'excerpt'        => $article['excerpt'] ?? '',
                        'keywords'       => $article['keywords'] ?? [],
                    ];
                }
            }
        }

        return $flat;
    }
}
