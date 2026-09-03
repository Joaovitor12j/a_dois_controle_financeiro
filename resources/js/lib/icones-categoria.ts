import {
    Car,
    Dog,
    Dumbbell,
    Gift,
    GraduationCap,
    HandHeart,
    HeartPulse,
    Home,
    type LucideIcon,
    MoreHorizontal,
    Plane,
    Popcorn,
    Receipt,
    Repeat,
    Shield,
    Shirt,
    ShoppingBag,
    Sparkles,
    Utensils,
    Zap,
} from 'lucide-react';

export const ICONES_CATEGORIA = {
    home: Home,
    utensils: Utensils,
    car: Car,
    'heart-pulse': HeartPulse,
    'graduation-cap': GraduationCap,
    popcorn: Popcorn,
    repeat: Repeat,
    'shopping-bag': ShoppingBag,
    dog: Dog,
    plane: Plane,
    dumbbell: Dumbbell,
    zap: Zap,
    shirt: Shirt,
    sparkles: Sparkles,
    gift: Gift,
    receipt: Receipt,
    shield: Shield,
    'hand-heart': HandHeart,
    'more-horizontal': MoreHorizontal,
} as const satisfies Record<string, LucideIcon>;

export type IconeCategoria = keyof typeof ICONES_CATEGORIA;

export const LISTA_ICONES_CATEGORIA = Object.keys(
    ICONES_CATEGORIA,
) as IconeCategoria[];

export function iconeCategoriaComponente(icone: string): LucideIcon {
    return (
        ICONES_CATEGORIA[icone as IconeCategoria] ?? ICONES_CATEGORIA.home
    );
}
