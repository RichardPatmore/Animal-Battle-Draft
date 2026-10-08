<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * Base draft categories. Membership is rule-driven and synced by CategoryAssigner.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
class CategorySeeder extends Seeder
{
	public function run(): void
	{
		$categories = [
			[
				'slug' => 'all-animals',
				'name' => 'All Animals',
				'description' => 'Anything goes. Elephants, ants and everything in between.',
				'icon' => 'globe',
				'rules' => [['field' => 'is_published', 'operator' => '=', 'value' => 'true']],
			],
			[
				'slug' => 'apex-predators',
				'name' => 'Apex Predators',
				'description' => 'Top of the food chain only. Nothing hunts these animals as adults.',
				'icon' => 'crown',
				'rules' => [['field' => 'is_apex_predator', 'operator' => '=', 'value' => 'true']],
			],
			[
				'slug' => 'mammals',
				'name' => 'Mammals',
				'description' => 'Warm-blooded, furry (mostly) and milk-fed.',
				'icon' => 'paw-print',
				'rules' => [['field' => 'taxon_class', 'operator' => '=', 'value' => 'Mammalia']],
			],
			[
				'slug' => 'birds',
				'name' => 'Birds',
				'description' => 'Feathered dinosaurs, from hummingbirds to ostriches.',
				'icon' => 'bird',
				'rules' => [['field' => 'taxon_class', 'operator' => '=', 'value' => 'Aves']],
			],
			[
				'slug' => 'reptiles-amphibians',
				'name' => 'Reptiles & Amphibians',
				'description' => 'Scales, shells and slime.',
				'icon' => 'turtle',
				'rules' => [['field' => 'taxon_class', 'operator' => 'in', 'value' => 'Reptilia, Amphibia']],
			],
			[
				'slug' => 'fish',
				'name' => 'Fish',
				'description' => 'Sharks, rays and bony fish.',
				'icon' => 'fish',
				'rules' => [['field' => 'taxon_class', 'operator' => 'in', 'value' => 'Actinopterygii, Chondrichthyes']],
			],
			[
				'slug' => 'bugs',
				'name' => 'Bugs & Creepy Crawlies',
				'description' => 'Insects, spiders, scorpions and centipedes. Stats are scaled within the pool, so size is relative.',
				'icon' => 'bug',
				'rules' => [['field' => 'taxon_class', 'operator' => 'in', 'value' => 'Insecta, Arachnida, Chilopoda']],
				'size_relative' => true,
			],
			[
				'slug' => 'ocean',
				'name' => 'Ocean Giants & Deep Divers',
				'description' => 'Everything that lives in the water.',
				'icon' => 'waves',
				'rules' => [['field' => 'domain', 'operator' => '=', 'value' => 'water']],
			],
			[
				'slug' => 'venomous',
				'name' => 'Venomous & Poisonous',
				'description' => 'Fangs, stingers, spines and toxic skin.',
				'icon' => 'skull',
				'rules' => [['field' => 'measurement.venom_score', 'operator' => '>=', 'value' => '5']],
			],
			[
				'slug' => 'threatened',
				'name' => 'Threatened Species',
				'description' => 'Animals listed as Vulnerable, Endangered or Critically Endangered by the IUCN.',
				'icon' => 'heart-handshake',
				'rules' => [['field' => 'conservation_status', 'operator' => 'in', 'value' => 'VU, EN, CR']],
			],
		];

		foreach ($categories as $index => $data) {
			Category::query()->updateOrCreate(
				['slug' => $data['slug']],
				[...$data, 'sort_order' => $index, 'is_active' => true],
			);
		}
	}
}
