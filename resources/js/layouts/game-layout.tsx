import { Link, usePage } from '@inertiajs/react';
import { BookOpen, PawPrint, Swords } from 'lucide-react';
import type { ReactNode } from 'react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import { ThemeToggle } from '@/components/theme-toggle';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn } from '@/lib/utils';
import { home } from '@/routes';
import animals from '@/routes/animals';

/**
 * Shell for every public game page: header navigation, flash toasts and data credits.
 */
export default function GameLayout({ children }: { children: ReactNode }) {
	const { flash } = usePage<{
		flash: { error?: string | null; success?: string | null };
	}>().props;
	const { isCurrentUrl } = useCurrentUrl();

	useEffect(() => {
		if (flash?.error) {
			toast.error(flash.error);
		}

		if (flash?.success) {
			toast.success(flash.success);
		}
	}, [flash?.error, flash?.success]);

	const nav = [
		{ title: 'Draft', href: home(), icon: Swords },
		{ title: 'Encyclopedia', href: animals.index(), icon: BookOpen },
	];

	return (
		<div className="flex min-h-screen flex-col bg-wild text-foreground">
			<header className="mx-auto flex w-full max-w-6xl items-center justify-between gap-4 px-4 py-4 sm:px-6">
				<Link href={home()} className="flex items-center gap-2.5">
					<span className="flex size-10 items-center justify-center rounded-xl bg-primary text-primary-foreground shadow-md">
						<PawPrint className="size-5" />
					</span>
					<span className="leading-tight">
						<span className="block text-[0.65rem] font-semibold tracking-[0.2em] text-primary uppercase">
							Draft · Build · Brawl
						</span>
						<span className="block font-heading text-lg font-bold">
							Animal Battle Draft
						</span>
					</span>
				</Link>
				<nav className="flex items-center gap-1">
					{nav.map((item) => (
						<Link
							key={item.title}
							href={item.href}
							className={cn(
								'inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium transition hover:bg-accent',
								isCurrentUrl(item.href) && 'bg-accent',
							)}
						>
							<item.icon className="size-4" />
							<span className="hidden sm:inline">
								{item.title}
							</span>
						</Link>
					))}
					<ThemeToggle />
				</nav>
			</header>

			<main className="mx-auto w-full max-w-6xl flex-1 px-4 pb-16 sm:px-6">
				{children}
			</main>

			<footer className="border-t border-border/60 py-6 text-center text-xs text-muted-foreground">
				<p className="mx-auto max-w-3xl px-4">
					Real-world data from{' '}
					<a
						className="underline"
						href="https://www.wikidata.org"
						target="_blank"
						rel="noreferrer"
					>
						Wikidata
					</a>
					,{' '}
					<a
						className="underline"
						href="https://en.wikipedia.org"
						target="_blank"
						rel="noreferrer"
					>
						Wikipedia
					</a>
					,{' '}
					<a
						className="underline"
						href="https://www.gbif.org"
						target="_blank"
						rel="noreferrer"
					>
						GBIF
					</a>{' '}
					and the{' '}
					<a
						className="underline"
						href="https://www.iucnredlist.org"
						target="_blank"
						rel="noreferrer"
					>
						IUCN Red List
					</a>
					, plus hand-researched estimates. Game stats are simplified
					for fun. Images credited on each card.
				</p>
			</footer>
		</div>
	);
}
