import {
	Bird,
	Brain,
	Bug,
	Crown,
	Dumbbell,
	Eye,
	Fish,
	Gauge,
	Ghost,
	Globe,
	HeartHandshake,
	HeartPulse,
	PawPrint,
	Shield,
	Skull,
	Swords,
	Turtle,
	Waves,
	Wind,
	type LucideIcon,
} from 'lucide-react';

const icons: Record<string, LucideIcon> = {
	gauge: Gauge,
	dumbbell: Dumbbell,
	'heart-pulse': HeartPulse,
	shield: Shield,
	swords: Swords,
	eye: Eye,
	brain: Brain,
	ghost: Ghost,
	wind: Wind,
	globe: Globe,
	crown: Crown,
	'paw-print': PawPrint,
	bird: Bird,
	turtle: Turtle,
	fish: Fish,
	bug: Bug,
	waves: Waves,
	skull: Skull,
	'heart-handshake': HeartHandshake,
};

/**
 * Renders a lucide icon from the string names used by the PHP enums and categories.
 */
export function NamedIcon({
	name,
	className,
}: {
	name: string | null | undefined;
	className?: string;
}) {
	const Icon = (name && icons[name]) || PawPrint;

	return <Icon className={className} aria-hidden />;
}
