import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { BarChart3, BedDouble, BookOpenText, CalendarDays, LayoutDashboard, UsersRound } from 'lucide-react';
import AppLogo from './app-logo';

const mainNavItems: NavItem[] = [
    {
        title: 'Panel',
        url: '/dashboard',
        icon: LayoutDashboard,
    },
    {
        title: 'Habitaciones',
        url: '/rooms',
        icon: BedDouble,
    },
    {
        title: 'Bitacora',
        url: '/logbook',
        icon: BookOpenText,
    },
    {
        title: 'Eventos',
        url: '/events',
        icon: CalendarDays,
    },
    {
        title: 'Analisis',
        url: '/analytics',
        icon: BarChart3,
    },
    {
        title: 'Empleados',
        url: '/employees',
        icon: UsersRound,
    },
];

const footerNavItems: NavItem[] = [];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/dashboard" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
