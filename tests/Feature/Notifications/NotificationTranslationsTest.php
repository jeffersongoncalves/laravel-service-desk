<?php

use Illuminate\Support\Facades\Lang;

it('has every notification translation key in each shipped locale', function (string $locale) {
    $keys = [];

    foreach (glob(dirname(__DIR__, 3).'/src/Notifications/*.php') as $file) {
        preg_match_all("/service-desk::service-desk\.notifications\.[a-z_.]+/", file_get_contents($file), $matches);
        $keys = array_merge($keys, $matches[0]);
    }

    // The operator notification builds its key from a variable prefix.
    foreach (['operator_new_ticket', 'operator_requester_reply'] as $prefix) {
        foreach (['subject', 'greeting', 'body', 'title', 'priority'] as $line) {
            $keys[] = "service-desk::service-desk.notifications.{$prefix}.{$line}";
        }
    }

    $keys = array_unique(array_filter($keys, fn (string $key): bool => ! str_ends_with($key, '.')));
    $missing = array_values(array_filter($keys, fn (string $key): bool => ! Lang::hasForLocale($key, $locale)));

    expect($keys)->not->toBeEmpty()
        ->and($missing)->toBe([]);
})->with(['en', 'pt_BR']);
