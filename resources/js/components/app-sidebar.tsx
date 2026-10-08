import { Link } from '@inertiajs/react';
import { BookOpen, Dices } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import {
	Sidebar,
	SidebarContent,
	SidebarFooter,
	SidebarHeader,
	SidebarMenu,
	SidebarMenuButton,
	SidebarMenuItem,
} from '@/components/ui/sidebar';
import { home } from '@/routes';
import animals from '@/routes/animals';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
	{
		title: 'Draft',
		href: home(),
		icon: Dices,
	},
	{
		title: 'Encyclopedia',
		href: animals.index(),
		icon: BookOpen,
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
							<Link href={home()} prefetch>
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
			</SidebarFooter>
		</Sidebar>
	);
}
