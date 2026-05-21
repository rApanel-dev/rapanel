import {
  HomeIcon,
  InformationCircleIcon,
  ArrowDownTrayIcon,
  TrophyIcon,
  HeartIcon,
  ArrowsRightLeftIcon,
  StarIcon
} from '@heroicons/vue/24/outline'

export const visitorMenuItems = [
  {
    name: 'Home',
    route: 'home',
    icon: HomeIcon,
  },
  {
    name: 'Information',
    icon: InformationCircleIcon,
    children: [
      { name: 'News',      route: 'news.index',    icon: '📰' },
      { name: 'Wiki',      route: 'info.wiki',     icon: '📖' },
      { name: 'Who Sell',  route: 'info.who-sell', icon: '🏪' },
      { name: 'MvP Cards', route: 'info.mvp-card', icon: '🃏' },
      { name: 'Item DB',   route: 'info.item-db',  icon: '🔎' },
    ],
  },
  {
    name: 'Downloads',
    route: 'downloads',
    icon: ArrowDownTrayIcon,
  },
  {
    name: 'Rankings',
    icon: TrophyIcon,
    children: [
      { name: 'Battlegrounds',   route: 'rank.battleground', icon: '🎖️' },
      { name: 'Guild vs Guild',  route: 'rank.woe-damage',   icon: '👑' },
      { name: 'War of Emperium', route: 'rank.woe',          icon: '🏰' },
      { name: 'MvP',             route: 'rank.mvps',         icon: '🐲' },
      { name: 'PvP',             route: 'rank.pvp',          icon: '🥊' },
      { name: 'Cash',            route: 'rank.cashpoints',   icon: '💵' },
      { name: 'Zeny',            route: 'rank.zeny',         icon: '💰' },
      { name: 'Playtime',        route: 'rank.playtime',     icon: '⌚' },
      { name: 'Level',           route: 'rank.leveling',     icon: '🏁' },
    ],
  },
  {
    name: 'Donations',
    route: 'donations',
    icon: HeartIcon,
  },
];

// NUEVO: Menú exclusivo para usuarios con sesión iniciada
export const authMenuItems = [
  {
    name: 'Transfer',
    route: 'account.transfer',
    icon: ArrowsRightLeftIcon,
  },
  {
    name: 'Vote 4 Points',
    route: 'vote',
    icon: StarIcon,
  },
];
