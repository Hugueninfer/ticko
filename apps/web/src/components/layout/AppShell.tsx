import { BookOpen, ChartLine, Clock, Inbox, Languages, LogOut, Moon, Sun, Users } from 'lucide-react';
import { useState, type ComponentType } from 'react';
import { useTranslation } from 'react-i18next';
import { NavLink, Outlet, useNavigate } from 'react-router';

import { useCurrentUser, useLogout } from '@/auth/useAuth';
import { Avatar } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { setLanguage, type Language } from '@/i18n';
import { useRealtimeConnection, useStaffUpdates } from '@/lib/realtime';
import { applyTheme, preferredTheme, type Theme } from '@/lib/theme';
import { cn, initials } from '@/lib/utils';

interface NavItem {
  to: string;
  label: string;
  icon: ComponentType<{ className?: string }>;
  soon?: boolean;
}

function SideLink({ item }: { item: NavItem }) {
  const { t } = useTranslation();
  const Icon = item.icon;

  if (item.soon) {
    return (
      <span className="flex items-center gap-2.5 rounded-md px-2.5 py-1.5 text-muted-foreground" aria-disabled>
        <Icon className="size-4" />
        <span className="flex-1">{item.label}</span>
        <Badge variant="muted">{t('common.comingSoon')}</Badge>
      </span>
    );
  }

  return (
    <NavLink
      to={item.to}
      className={({ isActive }) =>
        cn('flex items-center gap-2.5 rounded-md px-2.5 py-1.5 hover:bg-muted', isActive && 'bg-muted font-medium')
      }
    >
      <Icon className="size-4" />
      {item.label}
    </NavLink>
  );
}

export function AppShell() {
  const { t, i18n } = useTranslation();
  const navigate = useNavigate();
  const { data: user } = useCurrentUser();
  const logout = useLogout();
  const [theme, setTheme] = useState<Theme>(preferredTheme);

  // Live queue and conversation updates while signed in.
  useRealtimeConnection(Boolean(user));
  useStaffUpdates(user?.tenant?.id);

  const toggleTheme = () => {
    const next = theme === 'dark' ? 'light' : 'dark';
    applyTheme(next);
    setTheme(next);
  };

  const main: NavItem[] = [
    { to: '/tickets', label: t('nav.queue'), icon: Inbox },
    { to: '/knowledge', label: t('nav.knowledge'), icon: BookOpen, soon: true },
  ];
  const admin: NavItem[] = [
    { to: '/dashboard', label: t('nav.dashboard'), icon: ChartLine },
    { to: '/team', label: t('nav.team'), icon: Users },
    { to: '/categories', label: t('nav.categories'), icon: Clock },
  ];
  const company = user?.tenant?.name ?? t('app.name');

  return (
    <div className="flex h-full">
      <aside className="flex w-60 shrink-0 flex-col border-r bg-muted/40 p-3">
        <div className="mb-4 flex items-center gap-2.5 px-1">
          <span className="flex size-8 items-center justify-center rounded-md bg-primary text-xs font-bold text-primary-foreground">
            {initials(company)}
          </span>
          <div className="min-w-0 leading-tight">
            <p className="truncate font-semibold">{company}</p>
            <p className="text-xs text-muted-foreground">{t('app.name')}</p>
          </div>
        </div>

        <nav aria-label={t('nav.main')} className="flex flex-1 flex-col gap-0.5">
          {main.map((item) => (
            <SideLink key={item.to} item={item} />
          ))}
          {user?.role === 'admin' ? (
            <>
              <p className="mt-4 mb-1 px-2.5 text-[11px] font-semibold tracking-wide text-muted-foreground uppercase">
                {t('nav.admin')}
              </p>
              {admin.map((item) => (
                <SideLink key={item.to} item={item} />
              ))}
            </>
          ) : null}
        </nav>

        <div className="mt-3 flex items-center gap-1 border-t pt-3">
          <button
            type="button"
            onClick={toggleTheme}
            className="rounded-md p-2 hover:bg-muted"
            aria-label={theme === 'dark' ? t('theme.light') : t('theme.dark')}
          >
            {theme === 'dark' ? <Sun className="size-4" aria-hidden /> : <Moon className="size-4" aria-hidden />}
          </button>
          <label className="flex items-center gap-1 rounded-md p-1 hover:bg-muted">
            <Languages className="size-4" aria-hidden />
            <span className="sr-only">{t('language.label')}</span>
            <select
              className="bg-transparent text-xs"
              value={i18n.resolvedLanguage}
              onChange={(event) => setLanguage(event.target.value as Language)}
            >
              <option value="pt-BR">PT</option>
              <option value="en">EN</option>
            </select>
          </label>
        </div>

        {user ? (
          <div className="mt-2 flex items-center gap-2.5 px-1">
            <Avatar name={user.name} />
            <div className="min-w-0 flex-1 leading-tight">
              <p className="truncate font-medium">{user.name}</p>
              <p className="text-xs text-muted-foreground">{t(`roles.${user.role}`)}</p>
            </div>
            <button
              type="button"
              className="rounded-md p-2 hover:bg-muted"
              aria-label={t('nav.signOut')}
              onClick={() => logout.mutate(undefined, { onSettled: () => void navigate('/login', { replace: true }) })}
            >
              <LogOut className="size-4" aria-hidden />
            </button>
          </div>
        ) : null}
      </aside>

      <main className="min-w-0 flex-1 overflow-auto">
        <Outlet />
      </main>
    </div>
  );
}
