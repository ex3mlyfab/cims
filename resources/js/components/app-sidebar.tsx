import { Link } from '@inertiajs/react';
import { usePage } from '@inertiajs/react';
import {
    BedDouble,
    BookOpen,
    Building2,
    ClipboardList,
    FolderGit2,
    LayoutGrid,
    LockKeyhole,
    Palette,
    UserRoundSearch,
    Users,
    UsersRound,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { edit as editBranding } from '@/routes/branding';
import { dashboard } from '@/routes';
import { index as teamsIndex } from '@/routes/teams';
import { index as wardsIndex } from '@/routes/wards';
import { index as bedsIndex } from '@/routes/beds';
import { index as patientsIndex } from '@/routes/patients';
import { index as rolesIndex } from '@/routes/roles';
import { index as usersIndex } from '@/routes/users';
import type { NavItem } from '@/types';

const footerNavItems: NavItem[] = [
    
];

/**
 * CIMA-040: grouped administration shell (plan §5 — Clinical Operations /
 * Facility Management / Administration). Groups are permission-filtered on
 * the server's shared permission list; server route checks remain the
 * authority and hidden links are never relied on.
 */
export function AppSidebar() {
    const { permissions = [] } = usePage().props;

    const can = (...required: string[]): boolean =>
        required.some((permission) => permissions.includes(permission));

    const clinicalNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
        // CIMA-050: patient registry, the front door of the admission flow.
        ...(can('patients.view')
            ? [
                  {
                      title: 'Patients',
                      href: patientsIndex(),
                      icon: UserRoundSearch,
                  } as NavItem,
              ]
            : []),
        ...(can('encounters.manage')
            ? [
                  {
                      title: 'Admissions',
                      href: '/encounters',
                      icon: ClipboardList,
                  } as NavItem,
              ]
            : []),
        ...(can('statuses.view')
            ? [
                  {
                      title: 'Treatment statuses',
                      href: '/treatment-statuses',
                      icon: ClipboardList,
                  } as NavItem,
              ]
            : []),
    ];

    // Facility modules (teams, wards, beds) populate CIMA-043..046.
    const facilityNavItems: NavItem[] = [
        ...(can('teams.view')
            ? [
                  {
                      title: 'Teams',
                      href: teamsIndex(),
                      icon: UsersRound,
                  } as NavItem,
              ]
            : []),
        ...(can('wards.view')
            ? [
                  {
                      title: 'Wards',
                      href: wardsIndex(),
                      icon: Building2,
                  } as NavItem,
              ]
            : []),
        ...(can('beds.view')
            ? [
                  {
                      title: 'Beds',
                      href: bedsIndex(),
                      icon: BedDouble,
                  } as NavItem,
              ]
            : []),
    ];

    const adminNavItems: NavItem[] = [
        ...(can('users.view')
            ? [
                  {
                      title: 'Users',
                      href: usersIndex(),
                      icon:Users,
                  } as NavItem,
              ]
            : []),
        ...(can('roles.view')
            ? [
                  {
                      title: 'Roles & permissions',
                      href: rolesIndex(),
                      icon: LockKeyhole,
                  } as NavItem,
              ]
            : []),
        ...(can('settings.branding')
            ? [
                  {
                      title: 'Hospital branding',
                      href: editBranding(),
                      icon: Palette,
                  } as NavItem,
              ]
            : []),
    ];

    const groups = [
        {
            label: 'Clinical Operations',
            items: clinicalNavItems,
        },
        {
            label: 'Facility Management',
            items: facilityNavItems,
        },
        {
            label: 'Administration',
            items: adminNavItems,
        },
    ].filter((group) => group.items.length > 0);

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                {groups.map((group) => (
                    <NavMain
                        key={group.label}
                        label={group.label}
                        items={group.items}
                    />
                ))}
            </SidebarContent>

            <SidebarFooter>
                <NavFooter
                    items={footerNavItems}
                    className="mt-auto"
                />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
