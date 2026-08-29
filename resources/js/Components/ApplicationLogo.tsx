import { SVGAttributes } from 'react';

export default function ApplicationLogo({
    corUm = '#2F6F5E',
    corDois = '#7B3F55',
    ...props
}: SVGAttributes<SVGElement> & { corUm?: string; corDois?: string }) {
    return (
        <svg viewBox="0 0 64 40" fill="none" {...props}>
            <circle cx="24" cy="20" r="15" fill={corUm} fillOpacity="0.9" />
            <circle cx="40" cy="20" r="15" fill={corDois} fillOpacity="0.9" />
            <path
                d="M32 6.6a15 15 0 0 0 0 26.8 15 15 0 0 0 0-26.8Z"
                fill="#D9A441"
                fillOpacity="0.85"
            />
        </svg>
    );
}
