<?php

test('returns a successful response', function () {
    $response = $this->get(route('home'));

    $response->assertOk()
        ->assertJsonPath('documentation', url('/docs/api'));
});
