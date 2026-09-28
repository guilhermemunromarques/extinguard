<?php

test('application root requires authentication', function () {
    $this->get(route('home'))->assertRedirect(route('login'));
});
