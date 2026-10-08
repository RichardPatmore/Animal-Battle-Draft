export type StatKey =
	| 'strength'
	| 'health'
	| 'defence'
	| 'senses'
	| 'intelligence'
	| 'stealth'
	| 'agility';

export type StatOption = {
	value: StatKey;
	label: string;
	icon: string;
	description: string;
};

export type StatBlock = Record<StatKey, number>;

export type Conservation = {
	code: string;
	label: string;
	threatened: boolean;
	trend: string | null;
};

/**
 * Licence-free silhouette. `taxon` is set when the art shows a close relative.
 */
export type AnimalArt = {
	url: string;
	credit: string | null;
	licence: string | null;
	source_url: string | null;
	taxon: string | null;
};

export type AnimalCard = {
	id: number;
	slug: string;
	name: string;
	scientific_name: string | null;
	taxon_class: string | null;
	domain: string | null;
	diet: string | null;
	image_url: string | null;
	image_credit: string | null;
	image_licence: string | null;
	art: AnimalArt | null;
	habitat: string | null;
	conservation: Conservation | null;
	fact: { type: string; body: string } | null;
	stats: StatBlock;
};

export type AnimalProfile = AnimalCard & {
	description: string | null;
	taxon_order: string | null;
	taxon_family: string | null;
	regions: string[];
	habitats: string[];
	population_trend: string | null;
	wikipedia_url: string | null;
	facts: { type: string; body: string; source: string | null }[];
	measurements: {
		key: string;
		label: string;
		value: string;
		is_score: boolean;
		confidence: string;
		source: string | null;
		source_url: string | null;
	}[];
	categories: { slug: string; name: string }[];
};

export type DraftPick = {
	round: number;
	stat: StatKey;
	/** The drafted animal's own value for this stat. */
	value: number;
	/** The drafted animal's full stat block, used for blending. */
	stats: Partial<StatBlock>;
	animal: {
		slug: string;
		name: string;
		image_url: string | null;
		art: AnimalArt | null;
	};
};

export type Synergy = {
	key: string;
	label: string;
	description: string;
	bonus: number;
};

export type HybridContributor = {
	slug: string;
	name: string;
	art: AnimalArt | null;
};

export type HybridSummary = {
	id: string;
	hybrid_name: string | null;
	final_score: number | null;
	category: string;
	hybrid: Record<StatKey, number | null>;
	contributors: HybridContributor[];
};
