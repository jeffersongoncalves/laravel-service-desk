<?php

use JeffersonGoncalves\ServiceDesk\Mail\ThreadResolver;

beforeEach(function () {
    $this->resolver = new ThreadResolver;
});

it('returns null when all email fields are empty', function () {
    $result = $this->resolver->resolve([
        'in_reply_to' => null,
        'references' => null,
        'subject' => null,
    ]);

    expect($result)->toBeNull();
});

it('returns null when email data is minimal', function () {
    $result = $this->resolver->resolve([]);

    expect($result)->toBeNull();
});

it('returns null when subject has no reference pattern', function () {
    config()->set('service-desk.ticket.reference_prefix', 'SD');

    $result = $this->resolver->resolve([
        'in_reply_to' => null,
        'references' => null,
        'subject' => 'Just a normal subject line',
    ]);

    expect($result)->toBeNull();
});

it('returns null when in_reply_to is empty string', function () {
    $result = $this->resolver->resolve([
        'in_reply_to' => '',
        'references' => null,
        'subject' => null,
    ]);

    expect($result)->toBeNull();
});

it('returns null when references is empty string', function () {
    $result = $this->resolver->resolve([
        'in_reply_to' => null,
        'references' => '',
        'subject' => null,
    ]);

    expect($result)->toBeNull();
});

it('returns null when subject is empty string', function () {
    $result = $this->resolver->resolve([
        'in_reply_to' => null,
        'references' => null,
        'subject' => '',
    ]);

    expect($result)->toBeNull();
});
