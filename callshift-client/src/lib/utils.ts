import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

/**
 * Une nombres de clases condicionales resolviendo conflictos de Tailwind CSS.
 */
export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}

/**
 * Formatea minutos a formato legible "8h 30m"
 */
export function formatMinutesToHours(minutes: number): string {
  const hours = Math.floor(minutes / 60);
  const remainingMinutes = minutes % 60;
  if (remainingMinutes === 0) return `${hours}h`;
  return `${hours}h ${remainingMinutes}m`;
}

/**
 * Formatea hora militar "14:30:00" a formato amigable "14:30"
 */
export function formatTime(timeStr?: string | null): string {
  if (!timeStr) return '--:--';
  return timeStr.slice(0, 5);
}
