<?php

declare(strict_types=1);

/**
 * Editorial stories for The Journal.
 *
 * These are authored in code, not the CMS: a story needs a hero, byline,
 * structured blocks and product hooks that a single HTML blob cannot hold.
 */
class Journal
{
    /** @return list<array<string, mixed>> */
    public static function all(): array
    {
        return self::stories();
    }

    /** @return ?array<string, mixed> */
    public static function featured(): ?array
    {
        foreach (self::stories() as $story) {
            if (!empty($story['featured'])) {
                return $story;
            }
        }

        return self::stories()[0] ?? null;
    }

    /** @return ?array<string, mixed> */
    public static function bySlug(string $slug): ?array
    {
        foreach (self::stories() as $story) {
            if ($story['slug'] === $slug) {
                return $story;
            }
        }

        return null;
    }

    /**
     * Other stories for “more from the desk”, featured first.
     *
     * @return list<array<string, mixed>>
     */
    public static function except(string $slug, int $limit = 3): array
    {
        $others = array_values(array_filter(
            self::stories(),
            fn(array $s): bool => $s['slug'] !== $slug
        ));

        return array_slice($others, 0, $limit);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function stories(): array
    {
        return [
            [
                'slug'          => 'easy-runs-too-fast',
                'title'         => 'Why Your Easy Runs Are Too Fast',
                'kicker'        => 'Training',
                'dek'           => 'Most runners train in the grey zone — too hard to recover, too easy to improve. Slowing down four days a week is how you get faster on race day.',
                'hero'          => 'https://images.unsplash.com/photo-1552674605-db6ffd4facb5?w=1600&q=80',
                'hero_alt'      => 'Runner at full stride on an open road at dusk',
                'author'        => 'Amaka Okonkwo',
                'role'          => 'Running coach, Lagos',
                'date'          => '2026-07-28',
                'read_mins'     => 7,
                'featured'      => true,
                'product_slugs' => ['velocity-runner', 'stride-pulse-2', 'tempo-run-shorts'],
                'cta'           => ['label' => 'Shop running', 'href' => '/sport/running'],
                'pull_quote'    => 'If you cannot hold a full sentence, it is not an easy run. It is a workout you have not named.',
                'blocks'        => [
                    ['type' => 'p', 'text' => 'Ask a group of club runners what “easy” means and you will get five different answers, all of them too fast. The grey zone is comfortable enough to finish, hard enough to feel like work, and useless enough to stall you for a season.'],
                    ['type' => 'p', 'text' => 'In Lagos heat that mistake compounds. Heart rate sits higher for the same pace. Recovery takes longer. You show up to Thursday’s session already cooked, then wonder why the Saturday long run falls apart at kilometre 18.'],
                    ['type' => 'h2', 'text' => 'The 80/20 split, without the slogan'],
                    ['type' => 'p', 'text' => 'The research is not new. Roughly four in five kilometres should sit at a pace you could talk through. The remaining fifth is where the intervals, hills and race-pace work live. Most amateurs invert that: three “kinda hard” runs, one heroic interval session, and a long run that is neither long nor easy.'],
                    ['type' => 'callout', 'label' => 'The talk test', 'text' => 'On easy days you should be able to speak a full sentence without gasping. If you cannot, slow down — even if that means a walk break on Third Mainland. Pride does not build mitochondria.'],
                    ['type' => 'p', 'text' => 'Easy is not lazy. Easy is the work that lets hard days stay hard. When the aerobic base is real, race pace feels like a gear you already own instead of a panic you are borrowing.'],
                    ['type' => 'image', 'src' => 'https://images.unsplash.com/photo-1476480862126-209bfaa8edc8?w=1400&q=80', 'alt' => 'Group of runners on a quiet morning road', 'caption' => 'Easy pace is a conversation, not a time trial.'],
                    ['type' => 'h2', 'text' => 'How to actually slow down'],
                    ['type' => 'p', 'text' => 'Leave the watch face on heart rate or cadence, not pace, for four weeks. In this climate, “easy” might be 30–40 seconds slower per kilometre than your Strava graph suggests. That is not a failure of fitness. That is humidity doing what humidity does.'],
                    ['type' => 'p', 'text' => 'Pick routes that do not invite racing: the park loop at dawn, not the Lekki–Ikoyi bridge at 6pm. Train in a shoe that cushions the volume — the Velocity Runner and Stride Pulse 2 are built for this kind of mileage, not for looking fast in a photo.'],
                    ['type' => 'quote', 'text' => 'The grey zone feels productive. It is the most expensive feeling in amateur running.'],
                    ['type' => 'p', 'text' => 'Then, once a week, go properly hard. Short, honest intervals. Full recovery. No “and then I’ll jog home at tempo.” Name the session. Finish it. The easy days will have paid for it.'],
                ],
            ],
            [
                'slug'          => 'crimson-volt-returns',
                'title'         => 'Crimson Volt Returns. The Foam Did Not Leave.',
                'kicker'        => 'Drop',
                'dek'           => 'The Velocity Runner is back in the colourway that still gets stopped on towpaths. Same last. Cooler mesh. Summer miles, not a costume change.',
                'hero'          => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=1600&q=80',
                'hero_alt'      => 'Red performance running shoe on a dark surface',
                'author'        => 'BuyFirst Desk',
                'role'          => 'Product',
                'date'          => '2026-08-04',
                'read_mins'     => 4,
                'featured'      => false,
                'product_slugs' => ['velocity-runner', 'pace-chaser-trail'],
                'cta'           => ['label' => 'Shop the Velocity Runner', 'href' => '/product/velocity-runner'],
                'pull_quote'    => 'If you own an EU 43 in the first Velocity, buy an EU 43. The toe box is already generous. Do not second-guess a last that works.',
                'blocks'        => [
                    ['type' => 'p', 'text' => 'The original Crimson Volt colourway was never meant to be a limited story. It sold through because people actually ran in it, then asked for it back. This drop keeps the full-length foam and swaps the upper to a cooler engineered mesh for the months when Lagos does not cool down at 6am.'],
                    ['type' => 'p', 'text' => 'Nothing about the geometry changed. Heel-to-toe drop stays at 8mm. The ride is still that long, even return of energy — not a super-shoe stunt, not a brick. Daily trainer, built to be used four times a week.'],
                    ['type' => 'h2', 'text' => 'Who it is for'],
                    ['type' => 'p', 'text' => 'If your easy runs have started to feel like you are fighting the shoe, you are probably in a max-cushion tank. Velocity sits in the middle: enough foam for 10–16km, enough ground feel to stay honest. Coming from a maximal trainer, stay with your usual EU size — the toe box is already generous.'],
                    ['type' => 'callout', 'label' => 'Fit note', 'text' => 'True to the last version. Wide feet: stay. Between sizes: go up only if you wear thick socks in harmattan, not because the internet said so.'],
                    ['type' => 'p', 'text' => 'The mesh is the only thing we argued about. Summer miles in a dense knit is how blisters start. This upper dumps heat without going see-through. You will still look like you meant it on the park loop.'],
                    ['type' => 'image', 'src' => 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?w=1400&q=80', 'alt' => 'Sprinters leaving the blocks on a running track', 'caption' => 'Daily trainer energy. Not a race-day costume.'],
                    ['type' => 'p', 'text' => 'Stock is finite because the foam compound is finite. If you ran the first pair into the ground, this is the replacement. If you are new to the last, start with an easy week before you trust it on a long run.'],
                ],
            ],
            [
                'slug'          => 'apex-sits-low',
                'title'         => 'Apex Court Pro Sits Low. That Is The Point.',
                'kicker'        => 'Court',
                'dek'           => 'A basketball shoe is not a commute shoe with a higher collar. If you want cushion for the bus, look at Lifestyle. If you want to change direction, stay close to the floor.',
                'hero'          => 'https://images.unsplash.com/photo-1546519638-68e109498ffc?w=1600&q=80',
                'hero_alt'      => 'Outdoor basketball court at dusk',
                'author'        => 'Chinedu Bassey',
                'role'          => 'Player development',
                'date'          => '2026-06-19',
                'read_mins'     => 5,
                'featured'      => false,
                'product_slugs' => ['apex-court-pro', 'baseline-lo'],
                'cta'           => ['label' => 'Shop basketball', 'href' => '/sport/basketball'],
                'pull_quote'    => 'Cushion you cannot plant through is just delay. The cut happens in the first two centimetres, not the last twenty.',
                'blocks'        => [
                    ['type' => 'p', 'text' => 'Every season a good basketball shoe gets reviewed as “too low” by people who wanted a lifestyle trainer with a herringbone outsole. Apex Court Pro is not that product. The stack is short on purpose. You feel the floor because the floor is where the work is.'],
                    ['type' => 'p', 'text' => 'A high stack looks like protection. On a cut, it is a delay. The extra foam has to compress before you can plant, and by then the defender has your hip. Low-profile cushioning still takes the landing. It just does not ask you to wait for it.'],
                    ['type' => 'h2', 'text' => 'Traction before theatre'],
                    ['type' => 'p', 'text' => 'The wrap-around traction pattern is there for dusty outdoor courts as much as indoor hardwood. Lagos pickup is rarely a clean floor. Rubber that only works in a gym is a weekend toy. Apex is meant to grip through a change of direction when the surface is not doing you any favours.'],
                    ['type' => 'callout', 'label' => 'Wrong job', 'text' => 'If you want a cushioned pair for the commute and the office, that is Lifestyle — Metro Ease, Halo Glide, Baseline Lo. Do not force a court shoe into that role and then blame the collar.'],
                    ['type' => 'image', 'src' => 'https://images.unsplash.com/photo-1519861531473-920026367851?w=1400&q=80', 'alt' => 'Basketball going through a hoop', 'caption' => 'Stay low. Finish the play. Walk home in something else.'],
                    ['type' => 'p', 'text' => 'Padded collar, reinforced toe, 340g in EU 43. It is not a featherweight racing boot and it is not a dad shoe. It is a tool for players who create separation. If that is not you this month, buy the tool that matches the job you actually have.'],
                ],
            ],
            [
                'slug'          => 'lagos-dawn-miles',
                'title'         => 'Dawn Miles, Before The City Wakes Up',
                'kicker'        => 'Culture',
                'dek'           => 'The best running in Lagos happens before the generators, the traffic and the heat take the day. A short dispatch from the groups who already know.',
                'hero'          => 'https://images.unsplash.com/photo-1486218118823-0b0b0d504e2b?w=1600&q=80',
                'hero_alt'      => 'Runner on an empty road at sunrise',
                'author'        => 'Tomiwa Adeyemi',
                'role'          => 'Journal',
                'date'          => '2026-05-12',
                'read_mins'     => 6,
                'featured'      => false,
                'product_slugs' => ['velocity-runner', 'foundation-hoodie', 'core-performance-tee'],
                'cta'           => ['label' => 'Shop the kit', 'href' => '/sport/running'],
                'pull_quote'    => 'Five-thirty is not discipline as a personality. It is the only hour the air still belongs to you.',
                'blocks'        => [
                    ['type' => 'p', 'text' => 'By 6:40 the ring road is a different sport. Before that, it is still a loop: Muri Okunola, the park gates in Ikeja, the stretch past the lagoon when the water is the colour of tin. The groups that last in this city are the ones who agreed, without making a brand of it, that the day starts early or it does not start as a run.'],
                    ['type' => 'p', 'text' => 'Nobody here is chasing a Boston qualifier in January humidity for fun. They are protecting a piece of the week that cannot be moved to 7pm. Evening sessions exist. They are a different sport — heat, fumes, headlights. Dawn is the one that still feels like running.'],
                    ['type' => 'h2', 'text' => 'What you actually wear'],
                    ['type' => 'p', 'text' => 'Light tee, shorts with a zip that holds a phone, a shoe you trust on broken pavement. The Foundation Hoodie comes off by kilometre two most months of the year, but the 5:15 wait at the gate is cold enough to want it. That is the whole kit list. Anything else is a photoshoot.'],
                    ['type' => 'image', 'src' => 'https://images.unsplash.com/photo-1502904550040-7534592b73be?w=1400&q=80', 'alt' => 'Person stretching on a quiet waterfront at dawn', 'caption' => 'The city will be loud in an hour. This part does not have to be.'],
                    ['type' => 'p', 'text' => 'If you are new, do not hunt for the fastest pack on day one. Find the group that waits at traffic lights. The work is showing up on a Wednesday when you slept badly, not a Strava screenshot from a Saturday you already knew you could finish.'],
                    ['type' => 'callout', 'label' => 'Practical', 'text' => 'Reflective detail still matters at 5:40. Carry water even when the loop is “only” 8km. The humidity does not care that you felt fine at the gate.'],
                    ['type' => 'p', 'text' => 'We make gear for this hour, not for a campaign filmed in a cooler country. If the shoe cannot do a dawn loop on rough tarmac and still feel like itself on Sunday, it does not ship.'],
                ],
            ],
            [
                'slug'          => 'gear-that-lasts',
                'title'         => 'The Most Useful Climate Decision Is A Pair That Lasts',
                'kicker'        => 'Materials',
                'dek'           => 'We do not sell carbon offsets as a product. We publish the materials breakdown when the numbers are real — and we would rather repair a heel cup than replace a wardrobe.',
                'hero'          => 'https://images.unsplash.com/photo-1441974231531-c6227db76b6e?w=1600&q=80',
                'hero_alt'      => 'Sunlight through a dense forest canopy',
                'author'        => 'Ngozi Eze',
                'role'          => 'Materials',
                'date'          => '2026-04-22',
                'read_mins'     => 5,
                'featured'      => false,
                'product_slugs' => ['foundation-hoodie', 'core-performance-tee', 'momentum-leggings'],
                'cta'           => ['label' => 'Read our stance', 'href' => '/sustainability'],
                'pull_quote'    => 'A hoodie that holds its shape for four years beats a “green” drop that pills in six washes. Longevity is the climate feature.',
                'blocks'        => [
                    ['type' => 'p', 'text' => 'Gear should last more than a season. That is the most useful climate decision a clothing brand can make, and it is the least glamorous one to put on a hangtag. Recycled polyester in an upper that delaminates in five months is not a win. It is a story.'],
                    ['type' => 'p', 'text' => 'Most of our apparel uppers and linings now use recycled polyester. The warehouse in Ikeja runs on a renewable tariff. Last-mile partners in the city centres are chosen, in part, for EV fleets. None of that replaces the basic obligation: make a thing that does not need replacing because it failed.'],
                    ['type' => 'h2', 'text' => 'Repair before replace'],
                    ['type' => 'p', 'text' => 'Laces, heel cups, a blown out kangaroo pocket — these are common failures, not moral ones. We would rather send a repair guide than a discount code for the same hoodie in a new colourway. If we cannot stand behind a construction detail, it should not have shipped.'],
                    ['type' => 'callout', 'label' => 'What we will not do', 'text' => 'We will not sell offsets as a product, and we will not publish a materials pie chart until the numbers survive an audit. Pretty percentages are easy. True ones take a year.'],
                    ['type' => 'p', 'text' => 'The annual breakdown will live here in the Journal when it is ready — not in a campaign timed to Earth Day. Until then, the honest version is this: we are mid-transition, the cotton–recycled blend in the Foundation Hoodie is the piece we trust most, and we still have work on footwear outsoles.'],
                    ['type' => 'image', 'src' => 'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?w=1400&q=80', 'alt' => 'Folded natural-fibre textiles', 'caption' => 'Materials first. Marketing last.'],
                    ['type' => 'p', 'text' => 'If you want the policy page, it is under Company. If you want the kit that already lives up to this, start with the hoodie, the training tee, and the leggings we actually wear in the warehouse.'],
                ],
            ],
        ];
    }
}
