<?php
namespace App\Components\Home;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('home_hero')]
class HeroComponent
{
    public string $title;
    public string $subtitle;
}
