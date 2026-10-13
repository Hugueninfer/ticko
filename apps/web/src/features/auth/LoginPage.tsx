import { zodResolver } from '@hookform/resolvers/zod';
import { ApiError } from '@ticko/api-client';
import { ChartLine, ChevronRight, Headphones, Sparkles } from 'lucide-react';
import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { z } from 'zod';

import { isStaff, useDemoAvailability, useDemoLogin, useLogin, useLogout } from '@/auth/useAuth';
import { Button } from '@/components/ui/button';
import { FieldError, Input, Label } from '@/components/ui/input';

const TENANT_KEY = 'ticko.tenant';

function rememberedTenant(): string {
  try {
    return localStorage.getItem(TENANT_KEY) ?? '';
  } catch {
    return '';
  }
}

function useSchema() {
  const { t } = useTranslation();

  return z.object({
    tenant: z.string().trim().min(1, t('auth.required')).max(64),
    email: z.email(t('auth.invalidEmail')).max(254),
    password: z.string().min(1, t('auth.required')).max(255),
  });
}

type LoginForm = z.infer<ReturnType<typeof useSchema>>;

export function LoginPage() {
  const { t } = useTranslation();
  const schema = useSchema();
  const login = useLogin();
  const demo = useDemoAvailability();
  const demoLogin = useDemoLogin();
  const logout = useLogout();
  const [formError, setFormError] = useState<string | null>(null);

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<LoginForm>({
    resolver: zodResolver(schema),
    defaultValues: { tenant: rememberedTenant(), email: '', password: '' },
  });

  const handleError = (error: unknown) => {
    if (error instanceof ApiError && error.status === 429) {
      setFormError(t('auth.throttled'));
    } else if (error instanceof ApiError && error.status === 422) {
      setFormError(t('auth.invalid'));
    } else {
      setFormError(t('common.genericError'));
    }
  };

  const onSubmit = handleSubmit((values) => {
    setFormError(null);
    login.mutate(values, {
      onSuccess: (user) => {
        try {
          localStorage.setItem(TENANT_KEY, values.tenant);
        } catch {
          // Only a convenience.
        }

        // Staff are redirected by <RedirectIfSignedIn>; customers belong in the mobile app.
        if (!isStaff(user.role)) {
          logout.mutate();
          setFormError(t('auth.staffOnly'));
        }
      },
      onError: handleError,
    });
  });

  const signInAsDemo = (role: 'agent' | 'admin') => {
    setFormError(null);
    demoLogin.mutate(role, { onError: handleError });
  };

  const busy = login.isPending || demoLogin.isPending;

  return (
    <div className="grid min-h-full lg:grid-cols-2">
      <section className="flex items-center justify-center px-6 py-12">
        <div className="w-full max-w-sm">
          <div className="mb-8 flex items-center gap-2.5">
            <span className="flex size-8 items-center justify-center rounded-md bg-primary text-sm font-bold text-primary-foreground">
              T
            </span>
            <span className="text-base font-semibold">{t('app.name')}</span>
          </div>

          <h1 className="text-2xl font-semibold tracking-tight">{t('auth.title')}</h1>
          <p className="mt-1 text-muted-foreground">{t('auth.subtitle')}</p>

          {demo.data ? (
            <div className="mt-8">
              <p className="mb-2 text-[11px] font-semibold tracking-wide text-muted-foreground uppercase">
                {t('auth.demoTitle', { tenant: demo.data.tenant })}
              </p>
              <div className="grid gap-2">
                {(
                  [
                    ['agent', Headphones, t('auth.demoAgent'), t('auth.demoAgentHint')],
                    ['admin', ChartLine, t('auth.demoAdmin'), t('auth.demoAdminHint')],
                  ] as const
                ).map(([role, Icon, label, hint]) => (
                  <button
                    key={role}
                    type="button"
                    disabled={busy}
                    onClick={() => signInAsDemo(role)}
                    className="flex items-center gap-3 rounded-lg border p-3 text-left hover:bg-muted disabled:opacity-50"
                  >
                    <span className="flex size-9 items-center justify-center rounded-md bg-muted">
                      <Icon className="size-4" aria-hidden />
                    </span>
                    <span className="flex-1">
                      <span className="block font-medium">{label}</span>
                      <span className="block text-xs text-muted-foreground">{hint}</span>
                    </span>
                    <ChevronRight className="size-4 text-muted-foreground" aria-hidden />
                  </button>
                ))}
              </div>
              <div className="my-6 flex items-center gap-3 text-xs text-muted-foreground">
                <span className="h-px flex-1 bg-border" />
                {t('auth.or')}
                <span className="h-px flex-1 bg-border" />
              </div>
            </div>
          ) : (
            <div className="mt-8" />
          )}

          <form onSubmit={(event) => void onSubmit(event)} noValidate className="grid gap-4">
            <div className="grid gap-1.5">
              <Label htmlFor="tenant">{t('auth.company')}</Label>
              <Input
                id="tenant"
                autoComplete="organization"
                aria-invalid={errors.tenant ? true : undefined}
                aria-describedby="tenant-hint"
                {...register('tenant')}
              />
              <p id="tenant-hint" className="text-xs text-muted-foreground">
                {t('auth.companyHint')}
              </p>
              <FieldError>{errors.tenant?.message}</FieldError>
            </div>
            <div className="grid gap-1.5">
              <Label htmlFor="email">{t('auth.email')}</Label>
              <Input
                id="email"
                type="email"
                autoComplete="username"
                placeholder={t('auth.emailPlaceholder')}
                aria-invalid={errors.email ? true : undefined}
                {...register('email')}
              />
              <FieldError>{errors.email?.message}</FieldError>
            </div>
            <div className="grid gap-1.5">
              <Label htmlFor="password">{t('auth.password')}</Label>
              <Input
                id="password"
                type="password"
                autoComplete="current-password"
                aria-invalid={errors.password ? true : undefined}
                {...register('password')}
              />
              <FieldError>{errors.password?.message}</FieldError>
            </div>

            {formError ? (
              <p role="alert" className="rounded-md bg-danger-soft px-3 py-2 text-sm text-danger">
                {formError}
              </p>
            ) : null}

            <Button type="submit" size="lg" disabled={busy}>
              {login.isPending ? t('auth.submitting') : t('auth.submit')}
            </Button>
          </form>

          {demo.data ? <p className="mt-6 text-xs text-muted-foreground">{t('auth.demoNotice')}</p> : null}
        </div>
      </section>

      <aside className="hidden items-center justify-center border-l bg-muted/40 px-12 lg:flex" aria-hidden>
        <div className="max-w-md">
          <p className="text-xl leading-snug font-medium">{t('auth.pitchTitle')}</p>
          <div className="mt-6 rounded-lg border border-ai/30 bg-ai-soft p-4">
            <p className="flex items-center gap-1.5 text-xs font-medium text-ai">
              <Sparkles className="size-3.5" />
              {t('auth.pitchSuggestion')}
            </p>
            <p className="mt-2">{t('auth.pitchText')}</p>
            <span className="mt-3 inline-flex rounded-md bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground">
              {t('auth.pitchApprove')}
            </span>
          </div>
        </div>
      </aside>
    </div>
  );
}
