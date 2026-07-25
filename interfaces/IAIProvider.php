<?php

namespace SmartPick\Interfaces;

interface IAIProvider {
    public function query($prompt, $systemPrompt = '');
}
