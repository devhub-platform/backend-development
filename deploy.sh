#!/usr/bin/env bash
expose share https://devhub.test

# deploy the app using the default configuration exposed by the share command

use Illuminate\Support\Facades\Mail;

Mail::raw('Hello from AWS SES!', function ($message) {
    $message->to('jouugu@gmail.com')
            ->subject('Test Email');
});


# Good free models:
# 1) OpenAI: gpt-oss-20b (free)
# 2) OpenAI: gpt-oss-120b (free)
# 3) Z.ai: GLM 4.5 Air (free)
# 4) Qwen: Qwen3 Coder 480B A35B (free)
# 5) qwen/qwen3-coder:free
# 6) DeepSeek: DeepSeek V4 Flash (free)
# 7) MoonshotAI: Kimi K2.6 (free)
# 8) Google: Gemma 4 26B A4B (free)
# 9) NVIDIA: Nemotron 3 Super (free)
# 10) Qwen: Qwen3 Next 80B A3B Instruct (free)
# 11) Meta: Llama 3.3 70B Instruct (free)
# 12) Meta: Llama 3.2 3B Instruct (free)
# 13) Google: Gemma 4 31B (free)
# 14) Poolside: Laguna M.1 (free)
# 15) NVIDIA: Nemotron 3 Nano Omni (free)
# 16) 







9gwbk.{tag}@inbox.testmail.app

9gwbk.youssef123@inbox.testmail.app

9gwbk.ali123@inbox.testmail.app

9gwbk.asdsadad@inbox.testmail.app

Testmail service