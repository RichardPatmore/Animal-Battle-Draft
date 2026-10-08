import { Moon, Sun } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useAppearance } from '@/hooks/use-appearance';
import { cn } from '@/lib/utils';

type ThemeToggleProps = {
	className?: string;
};

/**
 * Switches explicitly between light and dark appearance (not system).
 */
export function ThemeToggle({ className }: ThemeToggleProps) {
	const { resolvedAppearance, updateAppearance } = useAppearance();
	const isDark = resolvedAppearance === 'dark';

	return (
		<Button
			type="button"
			variant="ghost"
			size="icon"
			className={cn('size-9 shrink-0', className)}
			onClick={() => updateAppearance(isDark ? 'light' : 'dark')}
			aria-label={
				isDark ? 'Switch to light mode' : 'Switch to dark mode'
			}
			title={isDark ? 'Light mode' : 'Dark mode'}
		>
			{isDark ? (
				<Sun className="size-5" />
			) : (
				<Moon className="size-5" />
			)}
		</Button>
	);
}
