<?php

namespace FOO;

/**
 * Class Fun
 * Fun things.
 * @package FOO
 */
class Fun {
    private static $ACCOMPLISHMENTS = [
        'has eaten exactly %d sandwiches over the course of their life!',
        'reached level %d.',
        'pet %d cats today.',
        'lost their pencil for the %dth time!',
        'predicted the end of the world in the year %d.',
        'successfully factored %d.',
        'is %d on the wait list.',
        'dropped their phone for the ℅dth time this week.',
        'has said "pizza" at least %d times today.',
        'rose to rank %d on the rock paper scissors leaderboard.',
        'has spent %d hours of their life using a computer.',
        'added %d items to their Etsy shop.',
        'just went through their %dth rodeo.',
        'deleted %d lines of old code.',
        'closed %d Alerts. Just kidding!',
        'closed a Jira ticket. Only %d tickets left!',
        'burned %d Calories today.',
        'has a %dGB album of cute dog photos.',
        'sent %d emails today.',
        'has won %d games of minesweeper.',
        'created a giant origami crane from %d sheets of paper.',
        'Finished a %d word dissertation on memes.',
    ];

    public static function accomplishment() {
        $n = 10 + (rand() % 99990);

        return sprintf(self::$ACCOMPLISHMENTS[array_rand(self::$ACCOMPLISHMENTS)], $n);
    }
}
