<?php

declare(strict_types=1);

namespace App\Content;

/**
 * Hand-written <title> and meta description for the pages that matter most
 * for search traffic.
 *
 * Every other page keeps the title and description its WordPress export
 * carried, tidied by App\Core\SeoText (length, site name, a description
 * generated from the opening paragraph when the export had none). These
 * pages get copy written for the query they rank for instead: the main
 * keyword first, 45-60 characters of title so it neither looks thin nor
 * gets cut off in results, and a 140-160 character description that
 * answers the query and gives a reason to click.
 *
 * Keyed by the English path. The Chinese mirror keeps its own (Chinese)
 * titles - see App\Core\MirrorTitle.
 */
final class SeoOverrides
{
    /** @var array<string, array{title?: string, description?: string}> */
    private const PAGES = [
        '/' => [
            'title' => 'How to Play Badminton: Rules, Techniques & Tips',
            'description' => 'Free badminton lessons for every level: rules and scoring, grips, footwork, serves, smashes and net play, plus racket and shoe buying guides.',
        ],
        '/badminton-rules.html' => [
            'title' => 'Badminton Rules: A Simple Guide for All Levels',
            'description' => 'Learn the basic badminton rules: rally scoring to 21, how to serve legally, in and out lines, faults and lets, explained simply with court diagrams.',
        ],
        '/badminton-court-size.html' => [
            'title' => 'Badminton Court Size & Dimensions (Metres and Feet)',
            'description' => 'A badminton court is 13.4 m long and 6.1 m wide for doubles (5.18 m for singles). Full measurements, net height and line markings in metres and feet.',
        ],
        '/badminton-scoring.html' => [
            'title' => 'Badminton Scoring System: How the 21-Point Rules Work',
            'description' => 'How badminton scoring works: rally scoring to 21 points, best of three games, winning by two, the 30-point cap, intervals and serving from odd or even.',
        ],
        '/badminton-racket.html' => [
            'title' => 'How to Choose a Badminton Racket: Weight, Balance & Flex',
            'description' => 'How to choose a badminton racket: what weight (3U, 4U), balance point, shaft stiffness and frame shape mean, and which suits a power or control player.',
        ],
        '/badminton-smash.html' => [
            'title' => 'Forehand Badminton Smash: Technique and Tips',
            'description' => 'The badminton smash step by step: get into position with the forehand grip, find a firm base, and hit down steeply from the rear court with power.',
        ],
        '/badminton-smash-technique.html' => [
            'title' => 'Badminton Smash Techniques: Types and When to Use Them',
            'description' => 'The types of badminton smash, the objective of the smash, the trade-off between power and accuracy, and key lessons for winning rallies with it.',
        ],
        '/badminton-equipment.html' => [
            'title' => 'Badminton Equipment Guide: Rackets, Shoes & Shuttles',
            'description' => 'What badminton equipment do you need? A buying guide to rackets, strings, grips, shoes, shuttlecocks and apparel, with tips for beginners and club players.',
        ],
        '/badminton-strokes.html' => [
            'title' => 'Badminton Strokes: Forehand, Backhand & Overhead Shots',
            'description' => 'Learn the basic badminton strokes correctly from the start: what a stroke is, why bad habits are hard to fix, and the forehand and backhand strokes.',
        ],
        '/badminton-techniques.html' => [
            'title' => 'Badminton Techniques, Shots and Skills for Every Level',
            'description' => 'A complete guide to badminton techniques: footwork, serves, clears, drops, smashes, drives and net play, with step-by-step tips to improve every shot.',
        ],
        '/badminton-net-play.html' => [
            'title' => 'Badminton Net Play: Net Shots, Kills and Lifts Explained',
            'description' => 'Win more points at the net. Learn the tumbling net shot, net kill, net lift and push, and the footwork and racket position that make net play work.',
        ],
        '/advanced-badminton-techniques.html' => [
            'title' => 'Advanced Badminton Techniques: Play Like the Pros',
            'description' => 'Ready for the next level? Advanced badminton techniques used by top players, from the attacking clear to advanced smashes, drops and net play.',
        ],
        '/badminton-serve.html' => [
            'title' => 'Badminton Serve: High, Low and Flick Serves Explained',
            'description' => 'Learn the three badminton serves: the high serve, the low serve and the flick serve, why a good serve matters, and how to hit each one step by step.',
        ],
        '/badminton-grip.html' => [
            'title' => 'Badminton Grip: How to Choose and Fit a Racket Grip',
            'description' => 'Choose the right badminton grip: towel vs synthetic overgrips, grip size and thickness, and how to replace a grip on your racket step by step.',
        ],
        '/badminton-footwork.html' => [
            'title' => 'Badminton Footwork: A Complete Guide for Beginners',
            'description' => 'Why footwork is the most important badminton skill: how to move efficiently around the court, tips to get to every shuttle in time, and key advice.',
        ],
        '/badminton-shoe.html' => [
            'title' => 'Badminton Shoes: How to Choose the Right Pair',
            'description' => 'How to choose badminton shoes: stability, cushioning and breathability, how good shoes prevent ankle injuries and blisters, and getting the right fit.',
        ],
        '/badminton-string.html' => [
            'title' => 'Badminton String Guide: Diameter and String Tension',
            'description' => 'Choose the best badminton string: how string diameter affects power and durability, and whether you should string at low or high tension.',
        ],
        '/badminton-shuttlecock.html' => [
            'title' => 'Badminton Shuttlecock Guide: Best Feather Shuttle Brands',
            'description' => 'Feather shuttlecock brands compared from experience: RSL Classic Tourney, Protech, Aeroplane Black Label and Victor, plus tips to make shuttles last longer.',
        ],
        '/badminton-apparel.html' => [
            'title' => 'Badminton Apparel: What to Wear to Play Badminton',
            'description' => 'What to wear to play badminton: quick-drying, lightweight clothing, the best colours for hot weather, getting the right fit, and sleeves vs sleeveless.',
        ],
        '/how-to-play-badminton.html' => [
            'title' => 'How to Play Badminton: Where to Start as a Beginner',
            'description' => 'New to badminton or want to play like a pro? Where to start: the basics to learn first, the habits that separate beginners from experienced players.',
        ],
        '/how-to-play-badminton-blog.html' => [
            'title' => 'Badminton Blog: Tips, Questions and Player Advice',
            'description' => 'The Master Badminton blog: answers to readers\' badminton questions on rules, technique, training and equipment, plus tips to improve your game.',
        ],
        '/badminton-basics.html' => [
            'title' => 'Badminton Basics for Beginners: Grip, Stance & Footwork',
            'description' => 'Start badminton the right way: the basic grip, ready stance, footwork, serve and the first shots every beginner should learn, explained step by step.',
        ],
        '/category/badminton-articles/badminton-basics.html' => [
            'title' => 'Badminton Basics for Beginners: Grip, Stance & Footwork',
            'description' => 'Start badminton the right way: the basic grip, ready stance, footwork, serve and the first shots every beginner should learn, explained step by step.',
        ],
        '/category/badminton-videos/badminton-basics.html' => [
            'title' => 'Badminton Basics for Beginners: Grip, Stance & Footwork',
            'description' => 'Start badminton the right way: the basic grip, ready stance, footwork, serve and the first shots every beginner should learn, explained step by step.',
        ],
        '/where-to-play-in-australia.html' => [
            'title' => 'Where to Play Badminton in Australia: Courts & Clubs',
            'description' => 'Find badminton courts, clubs and venues across Australia on an interactive map, with tips for finding social and competitive games near you.',
        ],
        '/uk-badminton-places-to-play.html' => [
            'title' => 'Where to Play Badminton in the UK: Find Courts Near You',
            'description' => 'Find places to play badminton in England and the UK: clubs, leisure centres and sessions, using Badminton England\'s search and local club listings.',
        ],
        '/badminton-tips.html' => [
            'title' => 'Badminton Tips: Wrist Action, Balance & Shot Placement',
            'description' => 'Practical badminton tips to win more rallies: use your wrist, keep your balance, place shots accurately and read your opponent before they hit.',
        ],
        '/badminton-articles.html' => [
            'title' => 'Badminton Articles: Guides and Tips to Improve Your Game',
            'description' => 'Badminton articles and resources on training, fitness, tactics, equipment and the history of the sport to help you understand and improve your game.',
        ],
        '/badminton-players.html' => [
            'title' => 'Best Badminton Players of All Time: Legends of the Game',
            'description' => 'Who are the best badminton players of all time? Profiles of legends such as Lin Dan, Lee Chong Wei and Taufik Hidayat, and what made them great.',
        ],
        '/badminton-videos.html' => [
            'title' => 'Badminton Videos: Classic Matches and Technique Clips',
            'description' => 'Watch badminton videos of classic finals and great rallies from the world\'s best players, and learn technique by studying how the pros play.',
        ],
        '/badminton-news.html' => [
            'title' => 'Badminton News: Major Tournaments, Results and Players',
            'description' => 'Badminton news and stories from major tournaments such as the All England, World Championships and Super Series, with results and player news.',
        ],
        '/professional-badminton-interview.html' => [
            'title' => 'Professional Badminton Player Interviews and Advice',
            'description' => 'Interviews with professional badminton players from Malaysia, India, Thailand, Singapore and the USA on training, mindset and how they reached the top.',
        ],
        '/badminton-rules-doubles.html' => [
            'title' => 'Badminton Doubles Rules: Serving, Scoring and Boundaries',
            'description' => 'Badminton doubles rules explained: service courts and rotation, in and out boundaries for doubles, scoring and common faults such as double hits.',
        ],
        '/badminton-fouls.html' => [
            'title' => 'Badminton Faults: 5 Common Fouls and How to Avoid Them',
            'description' => 'The 5 most common badminton faults: contact fault, over the net, service fault, receiver fault and double hit, with examples and the penalty for each.',
        ],
        '/badminton-singles.html' => [
            'title' => 'Badminton Singles Strategy: Skills and Shots to Win',
            'description' => 'What it takes to win at badminton singles: the physical fitness and mental strength it demands, the basic singles rules, and the key shots to master.',
        ],
        '/badminton-doubles.html' => [
            'title' => 'Badminton Doubles Strategy: Formations, Skills and Tips',
            'description' => 'How badminton doubles differs from singles: basic doubles strategy, returning your opponent\'s serve, and the attacking and defensive formations.',
        ],
        '/history-of-badminton.html' => [
            'title' => 'History of Badminton: How the Sport Began and Evolved',
            'description' => 'The history of badminton, from battledore and shuttlecock to the Olympic sport of today: where the game began, how its rules developed and key moments.',
        ],
        '/badminton-terms.html' => [
            'title' => 'Badminton Terms: A Glossary of Common Badminton Words',
            'description' => 'A glossary of common badminton terms every player should know, from clear, drop and smash to let, rally, fault, service court and match point.',
        ],
        '/badminton-clear.html' => [
            'title' => 'Badminton Clear: How to Hit a Good Defensive High Clear',
            'description' => 'Learn the badminton clear: what separates a good clear from a poor one, the objective of the defensive clear, and how to hit a deep high clear.',
        ],
        '/badminton-drop.html' => [
            'title' => 'Badminton Drop Shot: Fast and Slow Drops Explained',
            'description' => 'How to play the badminton drop shot: the fast and slow drop, the backhand drop, deception, and when a drop wins you the rally.',
        ],
        '/contact.html' => [
            'title' => 'Contact Master Badminton',
            'description' => 'Get in touch with Master Badminton: send a question about badminton rules, technique or equipment, or a note about the site.',
        ],
        '/privacy-policy.html' => [
            'title' => 'Privacy Policy - Master Badminton',
            'description' => 'How Master Badminton collects and uses information, including cookies, analytics and advertising, and the choices you have.',
        ],
        '/disclaimer' => [
            'title' => 'Disclaimer - Master Badminton',
            'description' => 'Terms and disclaimer for using the information, guides and links published on Master Badminton.',
        ],
        '/directory' => [
            'title' => 'New York Badminton Clubs Directory - Master Badminton',
            'description' => 'Badminton clubs and courts in New York City: addresses, phone numbers, websites and opening hours for clubs in Queens, Brooklyn and Flushing.',
        ],
        '/category/questions.html' => [
            'title' => 'Badminton Questions and Answers - Master Badminton',
            'description' => 'Answers to readers\' badminton questions on rules, serving, scoring, technique, training and equipment. Browse the questions or ask your own.',
        ],
    ];

    /**
     * The Chinese mirror's own overrides, keyed by the mirror path. Most
     * mirror pages are fine with their translated heading and a description
     * built from their opening paragraph; these are the ones where that
     * gives a poor result.
     *
     * @var array<string, array{title?: string, description?: string}>
     */
    private const PAGES_ZH = [
        '/zh' => [
            'title' => '如何打羽毛球：规则、技术与技巧 - Master Badminton',
            'description' => '免费的羽毛球教程，适合各个水平：比赛规则与计分、握拍、步法、发球、扣杀和网前技术，以及球拍和球鞋选购指南。',
        ],
        '/zh/contact.html' => [
            'title' => '联系我们 - Master Badminton',
            'description' => '联系 Master Badminton：提出关于羽毛球规则、技术或装备的问题，或对本站提出建议。',
        ],
        '/zh/badminton-court-size.html' => [
            'description' => '羽毛球场双打长 13.40 米、宽 6.10 米，单打宽 5.18 米。网高在网柱处为 1.55 米，中间为 1.524 米。',
        ],
        '/zh/badminton-rules.html' => [
            'description' => '羽毛球基本规则：每局 21 分每球得分制，三局两胜，发球规则、界内界外判定、犯规与重发球，简单易懂。',
        ],
        '/zh/badminton-scoring.html' => [
            'description' => '羽毛球计分规则：每球得分制，每局 21 分，三局两胜；20 平后须领先 2 分，29 平时先得第 30 分者获胜。',
        ],
    ];

    /** @return array{title?: string, description?: string} */
    public static function for(string $path): array
    {
        return self::PAGES[$path] ?? self::PAGES_ZH[$path] ?? [];
    }
}
