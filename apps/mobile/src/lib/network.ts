import { onlineManager } from '@tanstack/react-query';
import { useSyncExternalStore } from 'react';

/** Whether the phone is online, as TanStack Query sees it. */
export function useOnline(): boolean {
  return useSyncExternalStore(
    (onChange) => onlineManager.subscribe(onChange),
    () => onlineManager.isOnline(),
  );
}
