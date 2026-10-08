import { router } from '@inertiajs/react';
import { Sparkles } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import drafts from '@/routes/drafts';

/**
 * Shown on the result page when the hybrid still has the default "Creature" name.
 */
export function CreatureNaming({
	draftId,
	defaultName,
	suggestions,
	onDone,
}: {
	draftId: string;
	defaultName: string;
	suggestions: string[];
	onDone: () => void;
}) {
	const [custom, setCustom] = useState(suggestions[0] ?? '');
	const [saving, setSaving] = useState(false);

	const save = (name: string) => {
		setSaving(true);
		router.patch(
			drafts.name(draftId).url,
			{ hybrid_name: name },
			{
				preserveScroll: true,
				onFinish: () => {
					setSaving(false);
					onDone();
				},
			},
		);
	};

	return (
		<section
			className="rounded-2xl border border-primary/40 bg-card p-6 backdrop-blur"
			aria-labelledby="name-your-creature"
		>
			<h2
				id="name-your-creature"
				className="flex items-center gap-2 text-xl font-bold"
			>
				<Sparkles className="size-5 text-primary" /> Name your creature
			</h2>
			<p className="mt-1 text-sm text-muted-foreground">
				Every hybrid starts as &ldquo;{defaultName}&rdquo;. Pick a fun
				suggestion, type your own, or keep the default.
			</p>

			<div className="mt-4 flex flex-wrap gap-2">
				{suggestions.map((name) => (
					<button
						key={name}
						type="button"
						disabled={saving}
						onClick={() => save(name)}
						className={cn(
							'rounded-full border px-3 py-1.5 text-sm font-medium transition hover:border-primary hover:bg-accent',
							custom === name && 'border-primary bg-accent',
						)}
					>
						{name}
					</button>
				))}
			</div>

			<div className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
				<div className="min-w-0 flex-1 space-y-1">
					<label
						htmlFor="creature-custom-name"
						className="text-xs font-medium text-muted-foreground"
					>
						Custom name
					</label>
					<Input
						id="creature-custom-name"
						value={custom}
						maxLength={64}
						onChange={(e) => setCustom(e.target.value)}
						placeholder="Something fierce…"
					/>
				</div>
				<Button
					disabled={saving || custom.trim() === ''}
					onClick={() => save(custom.trim())}
				>
					Use this name
				</Button>
			</div>

			<div className="mt-4 flex justify-end border-t pt-4">
				<Button
					variant="ghost"
					disabled={saving}
					onClick={() => {
						onDone();
					}}
				>
					Keep &ldquo;{defaultName}&rdquo;
				</Button>
			</div>
		</section>
	);
}
