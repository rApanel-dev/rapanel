import {
  HomeIcon,
  InformationCircleIcon,
  ArrowDownTrayIcon,
  TrophyIcon,
  HeartIcon,
  UserIcon,             // NUEVO: Para Master Account
  ArrowsRightLeftIcon,  // NUEVO: Para Transfer
  StarIcon              // NUEVO: Para Vote
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
      { name: '📰 Wiki', route: 'info.wiki' },
      { name: '🏪 Who Sell', route: 'info.who-sell' },
      { name: '🃏 MvP Cards', route: 'info.mvp-card' },
      { name: '🔎 Item DB', route: 'info.item-db' },
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
      { name: '🎖️ Battlegrounds', route: 'rank.battleground' },
      { name: '👑 Guild vs Guild', route: 'rank.woe-damage' },
      { name: '🏰 War of Emperium', route: 'rank.woe' },
      { name: '🐲 MvP', route: 'rank.mvps' },
      { name: '🥊 PvP', route: 'rank.pvp' },
      { name: '💵 Cash', route: 'rank.cashpoints' },
      { name: '💰 Zeny', route: 'rank.zeny' },
      { name: '⌚ Playtime', route: 'rank.playtime' },
      { name: '🏁 Level', route: 'rank.leveling' },
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
