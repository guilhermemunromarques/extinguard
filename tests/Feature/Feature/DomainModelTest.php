<?php

test('application root requires authentication', function () {
    $this->get('/')->assertRedirect(route('login'));
});
