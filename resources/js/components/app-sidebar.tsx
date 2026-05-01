import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { BarChart3, BedDouble, BookOpenText, CalendarDays, ClipboardList, LayoutDashboard, PackageSearch, Scale, UsersRound, UtensilsCrossed } from 'lucide-react';
import AppLogo from './app-logo';

const operationsNav: NavItem[] = [
    { title: 'Panel', url: '/dashboard', icon: LayoutDashboard },
    { title: 'Habitaciones', url: '/rooms', icon: BedDouble },
    { title: 'Bitácora', url: '/logbook', icon: BookOpenText },
    { title: 'Eventos', url: '/events', icon: CalendarDays },
    { title: 'Empleados', url: '/employees', icon: UsersRound },
];

const restaurantNav: NavItem[] = [
    { title: 'Restaurante', url: '/restaurant', icon: UtensilsCrossed },
    { title: 'Recetas', url: '/recipes', icon: ClipboardList },
    { title: 'Stock cocina', url: '/kitchen-stock', icon: Scale },
    { title: 'Análisis restaurante', url: '/restaurant/analysis', icon: BarChart3 },
];

const managementNav: NavItem[] = [
    { title: 'Inventario', url: '/inventory', icon: PackageSearch },
    { title: 'Análisis general', url: '/analytics', icon: BarChart3 },
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
                <NavMain label="Operaciones" items={operationsNav} />
                <NavMain label="Restaurante" items={restaurantNav} />
                <NavMain label="Gestión" items={managementNav} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
