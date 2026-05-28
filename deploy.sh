#!/usr/bin/env bash
expose share https://devhub.test

# deploy the app using the default configuration exposed by the share command

use Illuminate\Support\Facades\Mail;

Mail::raw('Hello from AWS SES!', function ($message) {
    $message->to('jouugu@gmail.com')
            ->subject('Test Email');
});