import { zodResolver } from '@hookform/resolvers/zod';
import { ApiError, type Role } from '@ticko/api-client';
import { Check, Minus, Search } from 'lucide-react';
import { useEffect, useState } from 'react';
import { useForm } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { z } from 'zod';

import { useCurrentUser } from '@/auth/useAuth';
import { EmptyState, ErrorState, PageHeader } from '@/components/layout/States';
import { useToast } from '@/components/layout/toast-context';
import { Avatar } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, Skeleton } from '@/components/ui/card';
import { FieldError, Input, Label, NativeSelect } from '@/components/ui/input';

import { useCreateMember, useTeam, useUpdateMember } from './api';

const ROLES: Role[] = ['agent', 'admin', 'customer'];

function useMemberSchema() {
  const { t } = useTranslation();

  return z.object({
    name: z.string().trim().min(1, t('auth.required')).max(120),
    email: z.email(t('auth.invalidEmail')).max(254),
    role: z.enum(['agent', 'admin', 'customer']),
    password: z.string().min(12, t('team.passwordHint')).max(255),
  });
}

type MemberForm = z.infer<ReturnType<typeof useMemberSchema>>;

function AddMemberCard() {
  const { t } = useTranslation();
  const toast = useToast();
  const schema = useMemberSchema();
  const create = useCreateMember();
  const {
    register,
    handleSubmit,
    reset,
    setError,
    formState: { errors },
  } = useForm<MemberForm>({ resolver: zodResolver(schema), defaultValues: { name: '', email: '', role: 'agent', password: '' } });

  const onSubmit = handleSubmit((values) => {
    create.mutate(values, {
      onSuccess: () => {
        toast.success(t('team.created'));
        reset();
      },
      onError: (error) => {
        if (error instanceof ApiError && error.status === 422) {
          for (const [field, message] of Object.entries(error.fieldErrors())) {
            if (field in values) {
              setError(field as keyof MemberForm, { message });
            }
          }
        } else {
          toast.error(t('common.genericError'));
        }
      },
    });
  });

  return (
    <Card>
      <CardHeader>
        <CardTitle>{t('team.add')}</CardTitle>
      </CardHeader>
      <CardContent>
        <form className="grid gap-3" noValidate onSubmit={(event) => void onSubmit(event)}>
          <div className="grid gap-1.5">
            <Label htmlFor="member-name">{t('team.name')}</Label>
            <Input id="member-name" aria-invalid={errors.name ? true : undefined} {...register('name')} />
            <FieldError>{errors.name?.message}</FieldError>
          </div>
          <div className="grid gap-1.5">
            <Label htmlFor="member-email">{t('auth.email')}</Label>
            <Input id="member-email" type="email" aria-invalid={errors.email ? true : undefined} {...register('email')} />
            <FieldError>{errors.email?.message}</FieldError>
          </div>
          <div className="grid gap-1.5">
            <Label htmlFor="member-role">{t('team.columns.role')}</Label>
            <NativeSelect id="member-role" {...register('role')}>
              {ROLES.map((role) => (
                <option key={role} value={role}>
                  {t(`roles.${role}`)}
                </option>
              ))}
            </NativeSelect>
          </div>
          <div className="grid gap-1.5">
            <Label htmlFor="member-password">{t('team.initialPassword')}</Label>
            <Input
              id="member-password"
              type="password"
              autoComplete="new-password"
              aria-invalid={errors.password ? true : undefined}
              aria-describedby="member-password-hint"
              {...register('password')}
            />
            <p id="member-password-hint" className="text-xs text-muted-foreground">
              {t('team.passwordHint')}
            </p>
            <FieldError>{errors.password?.message === t('team.passwordHint') ? undefined : errors.password?.message}</FieldError>
          </div>
          <Button type="submit" disabled={create.isPending}>
            {create.isPending ? t('common.saving') : t('team.add')}
          </Button>
        </form>
      </CardContent>
    </Card>
  );
}

function PermissionsCard() {
  const { t } = useTranslation();
  const rows: [string, boolean, boolean][] = [
    [t('team.permissions.reply'), true, true],
    [t('team.permissions.notes'), true, true],
    [t('team.permissions.dashboard'), false, true],
    [t('team.permissions.manage'), false, true],
  ];

  return (
    <Card>
      <CardHeader>
        <CardTitle>{t('team.permissionsTitle')}</CardTitle>
      </CardHeader>
      <CardContent>
        <table className="w-full text-[13px]">
          <thead className="text-muted-foreground">
            <tr>
              <th className="py-1.5 text-left font-normal">
                <span className="sr-only">{t('team.permissionsTitle')}</span>
              </th>
              <th className="py-1.5 font-normal">{t('roles.agent')}</th>
              <th className="py-1.5 font-normal">{t('roles.admin')}</th>
            </tr>
          </thead>
          <tbody>
            {rows.map(([label, agent, admin]) => (
              <tr key={label} className="border-t">
                <td className="py-2">{label}</td>
                {[agent, admin].map((allowed, index) => (
                  <td key={index} className="py-2 text-center">
                    {allowed ? (
                      <Check className="mx-auto size-4 text-success" aria-label="✓" />
                    ) : (
                      <Minus className="mx-auto size-4 text-muted-foreground" aria-label="—" />
                    )}
                  </td>
                ))}
              </tr>
            ))}
          </tbody>
        </table>
      </CardContent>
    </Card>
  );
}

export function TeamPage() {
  const { t } = useTranslation();
  const toast = useToast();
  const { data: user } = useCurrentUser();
  const [search, setSearch] = useState('');
  const [q, setQ] = useState('');
  const team = useTeam(q ? { q } : {});
  const update = useUpdateMember();

  useEffect(() => {
    const timer = window.setTimeout(() => setQ(search.trim()), 300);

    return () => window.clearTimeout(timer);
  }, [search]);

  const change = (id: string, changes: { role?: Role; is_active?: boolean }) =>
    update.mutate(
      { id, changes },
      { onSuccess: () => toast.success(t('team.updated')), onError: () => toast.error(t('common.genericError')) },
    );

  return (
    <div>
      <PageHeader title={t('team.title')} subtitle={t('team.subtitle', { company: user?.tenant?.name ?? t('app.name') })} />
      <div className="grid gap-6 p-6 xl:grid-cols-[1fr_20rem]">
        <section className="min-w-0">
          <div className="relative mb-3 w-72">
            <Search className="absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground" aria-hidden />
            <Input
              type="search"
              className="pl-8"
              aria-label={t('common.search')}
              placeholder={t('team.searchPlaceholder')}
              value={search}
              onChange={(event) => setSearch(event.target.value)}
            />
          </div>

          {team.isError ? (
            <ErrorState onRetry={() => void team.refetch()} />
          ) : (
            <div className="overflow-x-auto rounded-lg border">
              <table className="w-full text-[13px]">
                <thead className="border-b bg-muted/40 text-left text-muted-foreground">
                  <tr>
                    <th className="px-3 py-2.5 font-medium">{t('team.columns.person')}</th>
                    <th className="px-3 py-2.5 font-medium">{t('team.columns.role')}</th>
                    <th className="px-3 py-2.5 font-medium">{t('team.columns.status')}</th>
                    <th className="px-3 py-2.5 font-medium">{t('team.columns.openTickets')}</th>
                    <th className="px-3 py-2.5">
                      <span className="sr-only">{t('common.edit')}</span>
                    </th>
                  </tr>
                </thead>
                <tbody>
                  {team.isPending ? (
                    <tr>
                      <td colSpan={5} className="p-3">
                        <Skeleton className="h-8" />
                      </td>
                    </tr>
                  ) : (
                    team.data.data.map((member) => {
                      const self = member.id === user?.id;

                      return (
                        <tr key={member.id} className="border-b last:border-0">
                          <td className="px-3 py-2.5">
                            <div className="flex items-center gap-2.5">
                              <Avatar name={member.name} />
                              <div className="min-w-0">
                                <p className="font-medium">
                                  {member.name} {self ? <span className="text-muted-foreground">({t('team.self')})</span> : null}
                                </p>
                                <p className="truncate text-muted-foreground">{member.email}</p>
                              </div>
                            </div>
                          </td>
                          <td className="px-3 py-2.5">
                            <NativeSelect
                              aria-label={t('team.roleOf', { name: member.name })}
                              value={member.role}
                              disabled={self || update.isPending}
                              onChange={(event) => change(member.id, { role: event.target.value as Role })}
                            >
                              {ROLES.map((role) => (
                                <option key={role} value={role}>
                                  {t(`roles.${role}`)}
                                </option>
                              ))}
                            </NativeSelect>
                          </td>
                          <td className="px-3 py-2.5">
                            <Badge variant={member.is_active ? 'success' : 'muted'}>
                              {member.is_active ? t('team.active') : t('team.inactive')}
                            </Badge>
                          </td>
                          <td className="px-3 py-2.5 font-mono">{member.open_tickets_count ?? t('common.none')}</td>
                          <td className="px-3 py-2.5 text-right">
                            {self ? null : (
                              <Button
                                variant={member.is_active ? 'destructive' : 'outline'}
                                size="sm"
                                disabled={update.isPending}
                                onClick={() => change(member.id, { is_active: !member.is_active })}
                              >
                                {member.is_active ? t('team.deactivate') : t('team.activate')}
                              </Button>
                            )}
                          </td>
                        </tr>
                      );
                    })
                  )}
                </tbody>
              </table>
              {!team.isPending && team.data.data.length === 0 ? <EmptyState title={t('team.empty')} /> : null}
            </div>
          )}
        </section>

        <div className="grid content-start gap-6">
          <AddMemberCard />
          <PermissionsCard />
        </div>
      </div>
    </div>
  );
}
