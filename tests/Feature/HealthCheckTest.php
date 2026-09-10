<?php

test('v1 health endpoint returns ok status', function () {
    $response = $this->getJson('/api/v1/health');

    $response->assertOk()->assertExactJson(['status' => 'ok']);
});

test('unversioned health endpoint is not registered', function () {
    $this->getJson('/api/health')->assertNotFound();
});
