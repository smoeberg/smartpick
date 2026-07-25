<?php
interface IAIProvider {
    public function query($prompt, $systemPrompt = '');
}
