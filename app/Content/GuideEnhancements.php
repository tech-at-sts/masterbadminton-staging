<?php

declare(strict_types=1);

namespace App\Content;

/**
 * Editorial additions to the site's most-searched guides.
 *
 * The exported articles are long and well structured, but most of them
 * open with scene-setting rather than the answer, and none carry a quick
 * fact table or a question-and-answer block. Search engines and AI answer
 * engines quote the first clear factual statement they find and lift
 * answers from Q&A blocks phrased the way people ask, so each guide here
 * gets:
 *
 *  - summary: a 2-3 sentence direct answer, shown at the top of the
 *    article body;
 *  - facts:   an optional block of reference material (tables, a
 *    diagram) added after the original text;
 *  - faq:     questions phrased the way people search, answered in a
 *    sentence or two, rendered at the end of the article and emitted as
 *    FAQPage structured data;
 *  - updated: the date this copy was last reviewed, used as the page's
 *    dateModified.
 *
 * The original article is never edited - these blocks are added around
 * it. Figures follow the BWF Laws of Badminton.
 *
 * Keyed by English path; the Chinese mirror is not touched.
 */
final class GuideEnhancements
{
    private const REVIEWED = '2026-11-27';

    /**
     * @return array{summary?: string, facts?: string, faq?: list<array{0: string, 1: string}>, updated?: string}
     */
    public static function for(string $path): array
    {
        $guides = self::guides();

        if (!isset($guides[$path])) {
            return [];
        }

        return $guides[$path] + ['updated' => self::REVIEWED];
    }

    /**
     * @return array<string, array{summary?: string, facts?: string, faq?: list<array{0: string, 1: string}>}>
     */
    private static function guides(): array
    {
        return [
            '/badminton-court-size.html' => [
                'summary' => 'A badminton court is <strong>13.40 m (44 ft) long</strong> and <strong>6.10 m (20 ft) wide</strong> for doubles. Singles uses the same length but a narrower <strong>5.18 m (17 ft)</strong> width. The net is <strong>1.55 m (5 ft 1 in)</strong> high at the posts and <strong>1.524 m (5 ft)</strong> at the centre.',
                'facts' => self::courtSizeFacts(),
                'faq' => [
                    ['How big is a badminton court?', 'A full badminton court is 13.40 m long and 6.10 m wide (44 ft x 20 ft), an area of about 81.7 m². That is the doubles court; the singles court is the same length but 5.18 m (17 ft) wide.'],
                    ['What is the difference between a singles and a doubles badminton court?', 'Only the width and the serving area. Singles uses the inner sidelines (5.18 m wide), doubles uses the outer sidelines (6.10 m wide). When serving in doubles the service court is shorter, ending at the doubles long service line 0.76 m inside the back boundary.'],
                    ['How high is a badminton net?', 'The top of the net is 1.55 m (5 ft 1 in) above the floor at the posts, which stand on the doubles sidelines, and 1.524 m (5 ft) at the centre of the court.'],
                    ['How far is the short service line from the net?', 'The short service line is 1.98 m (6 ft 6 in) from the net. A serve must land on or beyond this line to be good.'],
                    ['How wide are the lines on a badminton court?', 'All lines are 40 mm (about 1.5 in) wide, preferably white or yellow, and they count as part of the area they mark - a shuttle landing on the line is in.'],
                    ['How much space do I need for a badminton court?', 'The court itself needs 13.40 m x 6.10 m. Allow roughly 2 m of clear space around it for safe movement, so a room of about 17 m x 10 m fits one court comfortably. Leave as much ceiling height as possible: recreational halls typically have 7-9 m, and top international events require 12 m.'],
                ],
            ],

            '/badminton-rules.html' => [
                'summary' => 'Badminton is played to <strong>21 points</strong> with rally scoring, so every rally wins a point, and a match is the <strong>best of three games</strong>. The serve is hit underarm, below 1.15 m, diagonally into the opposite service court. A shuttle landing on a line is in, and you lose the rally if you touch the net, hit the shuttle twice or let it land in your court.',
                'facts' => self::table(
                    ['Rule', 'What it means'],
                    [
                        ['Points per game', '21, rally scoring (a point is scored on every rally)'],
                        ['Match format', 'Best of three games'],
                        ['Deuce', 'At 20-20 a side must lead by 2; at 29-29 the next point wins (cap of 30)'],
                        ['Serving height', 'The whole shuttle must be below 1.15 m from the floor when it is hit'],
                        ['Serving direction', 'Diagonally, into the opponent\'s service court'],
                        ['Line calls', 'A shuttle landing on the line is in'],
                        ['Intervals', 'Up to 60 s when the leading score reaches 11; up to 2 min between games'],
                    ],
                    'Badminton rules at a glance',
                ),
                'faq' => [
                    ['How many points is a badminton game?', 'A game is played to 21 points. If the score reaches 20-20, a side must win by two clear points, up to a maximum of 30 - at 29-29 the side that scores the 30th point wins.'],
                    ['How many games are in a badminton match?', 'A match is the best of three games, so the first side to win two games wins the match.'],
                    ['Is the line in or out in badminton?', 'The line is in. A shuttle that lands on any part of the boundary line counts as landing inside the court.'],
                    ['Can you touch the net in badminton?', 'No. Touching the net or its posts with your racket, body or clothing while the shuttle is in play is a fault, and you lose the rally.'],
                    ['Can your racket go over the net?', 'You must hit the shuttle on your own side of the net, but your racket may follow through over the net after contact as long as it does not touch the net.'],
                    ['How high can you serve in badminton?', 'The whole shuttle must be below 1.15 m from the court surface at the moment the server hits it, and the serve must be hit with an upward, underarm motion.'],
                ],
            ],

            '/badminton-scoring.html' => [
                'summary' => 'Badminton uses <strong>rally scoring to 21 points</strong>: whoever wins a rally scores a point and serves next, whether or not they served. A match is the <strong>best of three games</strong>. At 20-20 a side must lead by two, and at 29-29 the next point wins, so no game goes beyond 30.',
                'facts' => self::table(
                    ['Situation', 'Rule'],
                    [
                        ['Winning a game', 'First to 21 points'],
                        ['Score reaches 20-20', 'Play continues until one side leads by 2 points'],
                        ['Score reaches 29-29', 'The side that scores the 30th point wins the game'],
                        ['Winning a match', 'Win two games (best of three)'],
                        ['Who serves', 'The side that won the last rally'],
                        ['Serving court', 'Right court when the server\'s score is even, left court when odd'],
                        ['First serve of next game', 'The winner of the previous game'],
                        ['Change of ends', 'After each game, and in a third game when the leading score reaches 11'],
                    ],
                    'Badminton scoring at a glance',
                ),
                'faq' => [
                    ['How does scoring work in badminton?', 'Badminton uses rally scoring: a point is won on every rally, regardless of who served. The side that wins the rally adds a point and serves next. The first side to 21 wins the game.'],
                    ['What happens at 20-20 in badminton?', 'At 20-all, the game continues until one side leads by two points (for example 22-20). If it reaches 29-all, the side that wins the next rally wins the game 30-29.'],
                    ['Which side do you serve from in badminton?', 'Serve from the right service court when your score is even (0, 2, 4...) and from the left service court when it is odd (1, 3, 5...).'],
                    ['Do you have to win by 2 in badminton?', 'Yes, up to a point: from 20-20 a side needs a two-point lead to win, but the game is capped at 30, so at 29-29 the next point wins.'],
                    ['When did badminton change to 21-point scoring?', 'The Badminton World Federation adopted 21-point rally scoring in 2006, replacing the old system of 15-point games where only the serving side could score.'],
                ],
            ],

            '/badminton-rules-doubles.html' => [
                'summary' => 'In doubles the full 6.10 m-wide court is in play during a rally, but the <strong>service court is short and wide</strong>: it runs from the short service line to the doubles long service line, 0.76 m inside the back boundary. Each side has <strong>one serve</strong>, and partners only swap service courts when their side wins a point on their own serve.',
                'faq' => [
                    ['What are the boundaries in badminton doubles?', 'During a rally the doubles court is the full 13.40 m x 6.10 m. On the serve the receiving service court is shorter: it ends at the doubles long service line, 0.76 m inside the back boundary, and uses the outer (doubles) sideline.'],
                    ['How does serving work in doubles badminton?', 'Each side has a single serve. The server serves from the right court when their side\'s score is even and from the left when it is odd. When the serving side wins a point, the same player serves again from the other court; when it loses, the serve passes to the opponents.'],
                    ['Who receives the serve in doubles?', 'The player standing in the service court diagonally opposite the server. Only that player may return the serve; if their partner hits it, it is a fault.'],
                    ['Do partners switch sides in badminton doubles?', 'Only the serving side switches, and only when it wins a point on its own serve. The receiving side stays in the same courts until it wins the serve back.'],
                ],
            ],

            '/badminton-fouls.html' => [
                'summary' => 'A fault ends the rally and gives your opponent <strong>one point</strong> and the serve. The five most common badminton faults are touching the net, reaching over the net to hit the shuttle, an illegal serve, the receiver moving before the serve is struck, and hitting the shuttle twice.',
                'faq' => [
                    ['What happens when you commit a fault in badminton?', 'You lose the rally: your opponent scores one point and serves next.'],
                    ['What is a service fault in badminton?', 'A serve is a fault if the shuttle is above 1.15 m when hit, the server or receiver moves their feet before the serve is delivered, the serve lands outside the correct service court, or the server misses the shuttle.'],
                    ['Is it a fault if the shuttle hits your body?', 'Yes. If the shuttle touches you or your clothing during a rally, it is a fault and you lose the point.'],
                    ['Can you hit the shuttle twice in badminton?', 'No. Hitting the shuttle twice in succession, or both partners hitting it one after the other in doubles, is a double hit and a fault.'],
                ],
            ],

            '/badminton-serve.html' => [
                'summary' => 'There are three main badminton serves: the <strong>high serve</strong>, which sends the shuttle high and deep to the back line (used mostly in singles); the <strong>low serve</strong>, which skims the net and lands just past the short service line (used mostly in doubles); and the <strong>flick serve</strong>, which looks like a low serve but is flicked over the receiver\'s head.',
                'faq' => [
                    ['What are the rules for serving in badminton?', 'Serve underarm with the whole shuttle below 1.15 m from the floor at contact, from inside your service court, diagonally into the opponent\'s service court. Both feet must stay in contact with the floor until the serve is struck.'],
                    ['Which serve should I use in singles?', 'The high serve is the standard singles serve because it pushes your opponent to the back of the court. Mix in low serves to stop them anticipating.'],
                    ['Which serve should I use in doubles?', 'The low serve is the standard doubles serve because it stops the receiver attacking. Use the flick serve occasionally against a receiver who rushes the net.'],
                    ['Does a serve that clips the net count?', 'Yes. Under current rules a serve that touches the net and lands in the correct service court is good and play continues.'],
                ],
            ],

            '/badminton-racket.html' => [
                'summary' => 'Choose a badminton racket by <strong>weight</strong>, <strong>balance point</strong> and <strong>shaft stiffness</strong>. Most beginners do best with a light racket (4U, about 80-84 g), an even or slightly head-light balance and a flexible shaft; stronger players who want power often prefer a 3U head-heavy racket with a stiffer shaft.',
                'facts' => self::table(
                    ['Weight class', 'Unstrung weight', 'Suits'],
                    [
                        ['2U', '90-94 g', 'Strong players wanting maximum power and stability'],
                        ['3U', '85-89 g', 'Intermediate and advanced players; good power'],
                        ['4U', '80-84 g', 'Most beginners and all-round players; easy to swing'],
                        ['5U', '75-79 g', 'Juniors and players who want very fast handling'],
                    ],
                    'Badminton racket weight classes (Yonex scale, the most widely used)',
                ),
                'faq' => [
                    ['What weight badminton racket is best for beginners?', 'A 4U racket (about 80-84 g unstrung) suits most beginners: it is light enough to swing quickly and forgiving on the arm.'],
                    ['Head-heavy or head-light: which racket is better?', 'Head-heavy rackets add power to smashes and clears, which suits singles and rear-court players. Head-light rackets are faster to manoeuvre for defence and net play, which suits doubles. An even balance is a safe middle ground.'],
                    ['Should I choose a flexible or stiff badminton racket?', 'A flexible shaft helps players with slower swings generate power and is easier on the arm, so it suits beginners. A stiff shaft rewards a fast, well-timed swing with more control and is preferred by advanced players.'],
                    ['What string tension should a beginner use?', 'Most beginners play well at around 20-24 lbs. Lower tension gives a bigger sweet spot and more power; higher tension gives more control but needs good technique.'],
                ],
            ],

            '/badminton-shoe.html' => [
                'summary' => 'A good badminton shoe has a <strong>non-marking gum rubber sole</strong> for grip on indoor courts, a <strong>low, stable base</strong> for quick sideways movement, and <strong>cushioning</strong> under the heel and forefoot to absorb landings. Running shoes are a poor substitute: their raised, soft heels make it easier to roll an ankle when changing direction.',
                'faq' => [
                    ['Can I play badminton in running shoes?', 'It is not recommended. Running shoes are built for forward motion with a thick, soft heel, which makes the ankle less stable during the side-to-side movements and lunges badminton needs.'],
                    ['What should I look for in a badminton shoe?', 'Grip from a gum rubber, non-marking sole; lateral support and a low profile for stability; cushioning for landings; breathable upper; and a snug fit with about a thumb\'s width of room at the toe.'],
                    ['How often should I replace badminton shoes?', 'Replace them when the sole loses its grip or the cushioning feels flat - for regular club players that is often every 6-12 months.'],
                ],
            ],

            '/badminton-string.html' => [
                'summary' => 'Thinner badminton strings (around 0.61-0.66 mm) give more power, repulsion and feel but break sooner; thicker strings (0.68-0.70 mm and up) last longer. Higher <strong>string tension</strong> gives more control but a smaller sweet spot, so most beginners play best at about 20-24 lbs and advanced players at 24-30 lbs.',
                'faq' => [
                    ['What string tension should I use for badminton?', 'Beginners: about 20-24 lbs. Intermediate players: 22-26 lbs. Advanced players with a fast, clean swing: 24-30 lbs. Never exceed the maximum tension printed on your racket.'],
                    ['Is higher string tension better?', 'Not automatically. Higher tension gives more control and a crisper feel, but the sweet spot shrinks, off-centre hits lose power and strings break more easily. It only helps if your technique is good enough to hit the centre consistently.'],
                    ['How often should I restring my badminton racket?', 'Strings lose tension over time even when they do not break. A common guide is to restring as many times a year as you play per week.'],
                ],
            ],

            '/badminton-shuttlecock.html' => [
                'summary' => '<strong>Feather shuttlecocks</strong> (goose or duck feathers) are used in all serious competition because of their flight and feel; <strong>nylon shuttles</strong> are cheaper and far more durable, so they suit beginners and practice. Choose a shuttle speed to suit your hall: slower shuttles for warm or high-altitude venues, faster ones for cold halls.',
                'faq' => [
                    ['Feather or nylon shuttlecock: which is better?', 'Feather shuttles fly truer and are used in tournaments, but they break easily. Nylon shuttles last much longer and cost less, which makes them better for beginners, schools and casual games.'],
                    ['What does shuttlecock speed mean?', 'Shuttles are made in speeds (such as 76, 77 and 78, or numbered 1-5 by some brands) so that they travel the same distance in different conditions. Use a slower shuttle in hot weather or at altitude, where shuttles fly further, and a faster one in cold halls.'],
                    ['How can I make shuttlecocks last longer?', 'Store feather shuttles in a cool, slightly humid place (some players steam or humidify them) so the feathers stay flexible, and rotate several shuttles during a session.'],
                ],
            ],

            '/badminton-footwork.html' => [
                'summary' => 'Good badminton footwork starts from a balanced <strong>ready position</strong> near the centre of the court. Use a <strong>split step</strong> as your opponent hits, move with short chasse (shuffle) steps, reach the shuttle with a <strong>lunge</strong> at the net or a scissor jump at the back, then push back to the centre before the next shot.',
                'faq' => [
                    ['What is the most important footwork skill in badminton?', 'Recovering to the base position after every shot. Players who return to the centre quickly reach the next shuttle earlier and have time to choose a better shot.'],
                    ['What is a split step in badminton?', 'A small hop onto both feet, timed just as your opponent hits the shuttle. It loads your legs so you can push off in any direction the moment you see where the shuttle is going.'],
                    ['How can I improve my badminton footwork?', 'Practise shadow footwork to all six corners of the court, skipping and ladder drills for speed, and leg-strength exercises such as lunges and squats. Keep each movement short and balanced.'],
                ],
            ],

            '/badminton-smash.html' => [
                'summary' => 'The badminton smash is a powerful downward shot hit from the rear court. Get behind the shuttle early with a <strong>forehand grip</strong>, turn side-on, and hit it at the highest point you can reach, slightly in front of your body, snapping the forearm and wrist so the shuttle travels steeply down into your opponent\'s court.',
                'faq' => [
                    ['How do you hit a powerful smash in badminton?', 'Power comes from the whole body, not just the arm: turn side-on, transfer your weight from back to front foot, rotate your hips and shoulders, then pronate the forearm and snap the wrist at contact.'],
                    ['Where should you aim a smash?', 'Aim steeply down at your opponent\'s body, the hip or racket shoulder, or at the sidelines. A steep angle matters more than raw speed.'],
                    ['How fast is a badminton smash?', 'A good club smash travels well over 200 km/h off the racket, and top professionals have been measured at more than 400 km/h, the fastest shot in any racket sport.'],
                ],
            ],

            '/badminton-net-play.html' => [
                'summary' => 'Badminton net play uses three main shots: the <strong>net shot</strong> (a soft, tumbling shot that drops just over the net), the <strong>net kill</strong> (a fast downward hit when the shuttle is above the tape), and the <strong>net lift</strong> (a high defensive shot to the back of the court). Take the shuttle as high and as early as you can.',
                'faq' => [
                    ['What is a net kill in badminton?', 'A short, fast downward hit played when the shuttle is above the net tape. Use a small, quick tap rather than a big swing so you do not touch the net.'],
                    ['How do you play a spinning net shot?', 'Meet the shuttle close to the top of the net and slice across the base of the shuttle with a gentle touch, so the feathers tumble as it crosses. A tumbling shuttle is hard to return cleanly.'],
                    ['When should you lift instead of playing a net shot?', 'Lift when the shuttle has dropped well below the net and you cannot play a tight net shot, or when your opponent is already rushing the net. Lift high and deep to buy time.'],
                ],
            ],

            '/badminton-basics.html' => self::basics(),
            '/category/badminton-videos/badminton-basics.html' => self::basics(),
            '/category/badminton-articles/badminton-basics.html' => self::basics(),
        ];
    }

    /** @return array{summary: string, faq: list<array{0: string, 1: string}>} */
    private static function basics(): array
    {
        return [
            'summary' => 'Beginners should learn the badminton basics in this order: the <strong>forehand and backhand grips</strong>, a balanced <strong>ready stance</strong>, basic <strong>footwork</strong> to move and recover to the centre, the <strong>serve</strong>, and then the core shots - clear, drop, smash and net shot.',
            'faq' => [
                ['What should a beginner learn first in badminton?', 'The grip. Holding the racket correctly (a relaxed, handshake-style forehand grip, adjusted for backhands) makes every other stroke possible. Then learn the ready stance and footwork.'],
                ['How long does it take to learn badminton?', 'Most beginners can rally and play a casual game within a few sessions. Developing reliable technique for all the basic shots usually takes several months of regular play and practice.'],
                ['Is badminton good exercise?', 'Yes. Badminton combines short sprints, lunges and jumps, so it builds cardiovascular fitness, leg strength, agility and hand-eye coordination.'],
            ],
        ];
    }

    private static function courtSizeFacts(): string
    {
        $rows = [
            ['Court length', '13.40 m', '44 ft'],
            ['Court width (doubles)', '6.10 m', '20 ft'],
            ['Court width (singles)', '5.18 m', '17 ft'],
            ['Each half, net to back boundary', '6.70 m', '22 ft'],
            ['Net to short service line', '1.98 m', '6 ft 6 in'],
            ['Back boundary to doubles long service line', '0.76 m', '2 ft 6 in'],
            ['Side alley (singles to doubles sideline)', '0.46 m', '1 ft 6 in'],
            ['Service court depth (singles)', '4.72 m', '15 ft 6 in'],
            ['Service court depth (doubles)', '3.96 m', '13 ft'],
            ['Service court width (doubles)', '3.05 m', '10 ft'],
            ['Service court width (singles)', '2.59 m', '8 ft 6 in'],
            ['Net height at the posts', '1.55 m', '5 ft 1 in'],
            ['Net height at the centre', '1.524 m', '5 ft'],
            ['Line width', '40 mm', '1.5 in'],
            ['Court area (doubles / singles)', '81.7 m² / 69.4 m²', '880 ft² / 748 ft²'],
        ];

        $html = '<h2 id="court-dimensions">Badminton court dimensions in metres and feet</h2>'
            . '<p>All measurements follow the BWF Laws of Badminton and are taken to the outside edge of the lines, which are part of the court.</p>'
            . self::table(['Measurement', 'Metric', 'Imperial'], $rows, 'Badminton court dimensions');

        $html .= '<h2 id="singles-vs-doubles">Singles vs doubles court</h2>'
            . '<p>Singles and doubles are played on the same court with different boundaries. The diagram shows both: the <strong>singles</strong> court uses the inner sidelines and runs the full length to the back boundary; the <strong>doubles</strong> court uses the outer sidelines. On the serve, the doubles service court stops at the doubles long service line, 0.76 m short of the back boundary.</p>'
            . self::courtDiagram();

        $html .= '<h2 id="net-and-posts">Net and posts</h2>'
            . '<ul>'
            . '<li>The top of the net is <strong>1.55 m</strong> from the floor at the posts and <strong>1.524 m</strong> at the centre.</li>'
            . '<li>The posts stand on the doubles sidelines, even when singles is being played.</li>'
            . '<li>The net is 760 mm deep, made of fine dark cord, with a 75 mm white tape folded over the top cord.</li>'
            . '</ul>';

        $html .= '<h2 id="space-needed">How much space you need</h2>'
            . '<p>The playing area is 13.40 m x 6.10 m, but players need room to move outside the lines. Allow roughly 2 m of clear space around the court, so a space of about 17 m x 10 m fits a single court comfortably. Ceiling height matters too: high clears need room, so recreational halls typically have 7-9 m of clearance, while top international events require 12 m.</p>';

        return $html;
    }

    /** An inline, theme-neutral SVG plan of the court with singles and doubles boundaries. */
    private static function courtDiagram(): string
    {
        // Drawn to scale at 40 px per metre: 13.40 m x 6.10 m => 536 x 244,
        // laid out lengthways with the net in the middle.
        $m = static fn (float $metres): string => rtrim(rtrim(number_format($metres * 40, 1, '.', ''), '0'), '.');

        $x0 = 30;
        $y0 = 30;
        $len = 13.40;
        $wid = 6.10;
        $side = 0.46;
        $short = 1.98;
        $longDoubles = 0.76;

        $X = static fn (float $metres): string => (string) ($x0 + (float) $m($metres));
        $Y = static fn (float $metres): string => (string) ($y0 + (float) $m($metres));

        $svg = '<figure class="court-diagram">'
            . '<svg viewBox="0 0 596 330" role="img" aria-labelledby="court-diagram-title court-diagram-desc" xmlns="http://www.w3.org/2000/svg">'
            . '<title id="court-diagram-title">Badminton court diagram</title>'
            . '<desc id="court-diagram-desc">Plan view of a badminton court, 13.40 m long and 6.10 m wide, showing the singles sidelines 0.46 m inside the doubles sidelines, the short service lines 1.98 m from the net, and the doubles long service lines 0.76 m inside each back boundary.</desc>'
            . '<rect x="' . $X(0) . '" y="' . $Y(0) . '" width="' . $m($len) . '" height="' . $m($wid) . '" fill="#2f7d4f" />'
            // singles area tint
            . '<rect x="' . $X(0) . '" y="' . $Y($side) . '" width="' . $m($len) . '" height="' . $m($wid - 2 * $side) . '" fill="#368c59" />'
            . '<g fill="none" stroke="#fff" stroke-width="2">'
            . '<rect x="' . $X(0) . '" y="' . $Y(0) . '" width="' . $m($len) . '" height="' . $m($wid) . '" />'
            // singles sidelines
            . '<line x1="' . $X(0) . '" y1="' . $Y($side) . '" x2="' . $X($len) . '" y2="' . $Y($side) . '" />'
            . '<line x1="' . $X(0) . '" y1="' . $Y($wid - $side) . '" x2="' . $X($len) . '" y2="' . $Y($wid - $side) . '" />'
            // short service lines
            . '<line x1="' . $X($len / 2 - $short) . '" y1="' . $Y(0) . '" x2="' . $X($len / 2 - $short) . '" y2="' . $Y($wid) . '" />'
            . '<line x1="' . $X($len / 2 + $short) . '" y1="' . $Y(0) . '" x2="' . $X($len / 2 + $short) . '" y2="' . $Y($wid) . '" />'
            // doubles long service lines
            . '<line x1="' . $X($longDoubles) . '" y1="' . $Y(0) . '" x2="' . $X($longDoubles) . '" y2="' . $Y($wid) . '" />'
            . '<line x1="' . $X($len - $longDoubles) . '" y1="' . $Y(0) . '" x2="' . $X($len - $longDoubles) . '" y2="' . $Y($wid) . '" />'
            // centre lines (back boundary to short service line, each half)
            . '<line x1="' . $X(0) . '" y1="' . $Y($wid / 2) . '" x2="' . $X($len / 2 - $short) . '" y2="' . $Y($wid / 2) . '" />'
            . '<line x1="' . $X($len / 2 + $short) . '" y1="' . $Y($wid / 2) . '" x2="' . $X($len) . '" y2="' . $Y($wid / 2) . '" />'
            . '</g>'
            // net
            . '<line x1="' . $X($len / 2) . '" y1="' . ($y0 - 10) . '" x2="' . $X($len / 2) . '" y2="' . ((float) $Y($wid) + 10) . '" stroke="#111" stroke-width="4" />'
            . '<g font-family="system-ui, sans-serif" font-size="12" fill="currentColor">'
            . '<text x="' . $X($len / 2) . '" y="18" text-anchor="middle">Net 1.55 m (posts) / 1.524 m (centre)</text>'
            . '<text x="' . $X($len / 2) . '" y="' . ((float) $Y($wid) + 30) . '" text-anchor="middle">Length 13.40 m (44 ft)</text>'
            . '<text x="12" y="' . $Y($wid / 2) . '" text-anchor="middle" transform="rotate(-90 12 ' . $Y($wid / 2) . ')">Doubles 6.10 m</text>'
            . '<text x="' . ((float) $X($len) + 18) . '" y="' . $Y($wid / 2) . '" text-anchor="middle" transform="rotate(90 ' . ((float) $X($len) + 18) . ' ' . $Y($wid / 2) . ')">Singles 5.18 m</text>'
            . '</g>'
            . '<g font-family="system-ui, sans-serif" font-size="10" fill="#fff">'
            . '<text x="' . ((float) $X($len / 2 - $short) - 4) . '" y="' . ((float) $Y(0) + 14) . '" text-anchor="end">short service 1.98 m</text>'
            . '<text x="' . ((float) $X($longDoubles) + 4) . '" y="' . ((float) $Y($wid) - 6) . '">doubles long service 0.76 m</text>'
            . '<text x="' . ((float) $X($len - $longDoubles) - 4) . '" y="' . ((float) $Y($side) - 5) . '" text-anchor="end">side alley 0.46 m</text>'
            . '</g>'
            . '</svg>'
            . '<figcaption>Badminton court plan, drawn to scale. Lighter green: the singles court. Full width: the doubles court.</figcaption>'
            . '</figure>';

        return $svg;
    }

    /**
     * @param list<string> $head
     * @param list<list<string>> $rows
     */
    private static function table(array $head, array $rows, string $caption): string
    {
        $e = static fn (string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');

        $html = '<div class="guide-table-wrap"><table class="guide-table"><caption>' . $e($caption) . '</caption><thead><tr>';

        foreach ($head as $cell) {
            $html .= '<th scope="col">' . $e($cell) . '</th>';
        }

        $html .= '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $html .= '<tr>';

            foreach ($row as $i => $cell) {
                $html .= $i === 0 ? '<th scope="row">' . $e($cell) . '</th>' : '<td>' . $e($cell) . '</td>';
            }

            $html .= '</tr>';
        }

        return $html . '</tbody></table></div>';
    }
}
