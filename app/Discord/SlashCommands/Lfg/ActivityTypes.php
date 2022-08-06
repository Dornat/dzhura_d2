<?php

namespace App\Discord\SlashCommands\Lfg;

use JetBrains\PhpStorm\ArrayShape;

class ActivityTypes
{
    public const RAID = 'raid';
    public const RAID_SELECT = 'raid_select';
    public const RAID_VOG = 'vog';
    public const RAID_LW = 'lw';
    public const RAID_GOS = 'gos';
    public const RAID_DSC = 'dsc';
    public const RAID_VOD = 'vod';

    public const PVP = 'pvp';
    public const PVE = 'pve';
    public const GAMBIT = 'gambit';
    public const OTHER = 'other';

    #[ArrayShape([self::RAID => "string[]", self::PVP => "string[]", self::PVE => "string[]", self::GAMBIT => "string[]", self::OTHER => "string[]"])]
    public static function list(): array
    {
        return [
            self::RAID => [
                'label' => 'Рейд',
                'thumbnail' => 'https://www.bungie.net/common/destiny2_content/icons/8b1bfd1c1ce1cab51d23c78235a6e067.png',
                'color' => '#f0c907',
                'types' => [
                    self::RAID_DSC => [
                        'label' => 'Склеп глибокого каменю (DSC)',
                        'image' => 'https://d1lss44hh2trtw.cloudfront.net/assets/article/2020/11/21/deep-stone-crypt-raid-guide-destiny-2_feature.jpg'
                    ],
                    self::RAID_GOS => [
                        'label' => 'Сад спасіння (GOS)',
                        'image' => 'https://wallpapercave.com/wp/wp10912880.jpg'
                    ],
                    self::RAID_LW => [
                        'label' => 'Останнє бажання (LW)',
                        'image' => 'https://static1.srcdn.com/wordpress/wp-content/uploads/2021/09/Destiny-2-Riven-Boss-Guide-Last-Wish-Raid.jpg'
                    ],
                    self::RAID_VOD => [
                        'label' => 'Клятва послушника (VOD)',
                        'image' => 'https://destiny.wiki.gallery/images/thumb/0/0e/Vow_of_the_Disciple.jpg/1200px-Vow_of_the_Disciple.jpg'
                    ],
                    self::RAID_VOG => [
                        'label' => 'Кришталевий чертог (VOG)',
                        'image' => 'https://assets.reedpopcdn.com/destiny_2_vault_of_glass_walkthrough_4.jpg/BROK/resize/1200x1200%3E/format/jpg/quality/70/destiny_2_vault_of_glass_walkthrough_4.jpg'
                    ],
                ]
            ],
            self::PVP => [
                'label' => 'PVP',
                'thumbnail' => 'https://www.bungie.net//common/destiny2_content/icons/cc8e6eea2300a1e27832d52e9453a227.png',
                'color' => '#f00707',
                'image' => 'https://i.ytimg.com/vi/A5YRDsXxxOE/maxresdefault.jpg'
            ],
            self::PVE => [
                'label' => 'PVE',
                'thumbnail' => 'https://www.bungie.net/common/destiny2_content/icons/f2154b781b36b19760efcb23695c66fe.png',
                'color' => '#024ad9',
                'image' => 'https://images.mein-mmo.de/medien/2020/12/Taniks-Raid-Boss-Krypta-solo-zavala-Destiny-2-Titel.jpg'
            ],
            self::GAMBIT => [
                'label' => 'Ґамбіт',
                'thumbnail' => 'https://www.bungie.net/common/destiny2_content/icons/fc31e8ede7cc15908d6e2dfac25d78ff.png',
                'color' => '#00b80c',
                'image' => 'https://cdn.mos.cms.futurecdn.net/e7arhBHQobsuzEUvCH2kfa.png'
            ],
            self::OTHER => [
                'label' => 'Інше',
                'thumbnail' => 'https://png2.cleanpng.com/sh/0cb9e768019d24d86f4e5055d88f6bf2/L0KzQYq3VsEyN517R91yc4Pzfri0hPV0fJpzkZ87LXTog8XwjwkufJlqReZqa3XxPbzwjvcucJJxh597ZXHmeH79ifRmNaV3eehubHX1PbXskCRqdqoyjORqboPzccPsjwQuaZ51ReJ3Zz3mfLr3ggJ1NZd3fZ8AY3bpRrS9g8NkQWNqT5CBNEK5QIiCVsE2PmE3TKU8MEi1RIm4TwBvbz==/kisspng-destiny-2-destiny-the-taken-king-halo-reach-vide-traveler-destiny-transparent-amp-png-clipart-fre-5cff6c6c3c92e7.6426079615602433082481.png',
                'color' => '#878787'
            ]
        ];
    }
}
