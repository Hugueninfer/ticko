import { zodResolver } from '@hookform/resolvers/zod';
import { ApiError, type Category, type CategoryInput, type TicketPriority } from '@ticko/api-client';
import { Plus } from 'lucide-react';
import { useState } from 'react';
import { useForm, type FieldErrors } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { z } from 'zod';

import { EmptyState, ErrorState, PageHeader } from '@/components/layout/States';
import { useToast } from '@/components/layout/toast-context';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/card';
import { Dialog, DialogClose, DialogContent, DialogTrigger } from '@/components/ui/dialog';
import { FieldError, Input, Label, NativeSelect, Textarea } from '@/components/ui/input';
import { PriorityBadge } from '@/features/tickets/components/badges';
import { PRIORITIES } from '@/features/tickets/ticket-meta';
import { cn } from '@/lib/utils';

import { useCategories, useDeleteCategory, useSaveCategory } from './api';

type Unit = 'min' | 'h';

// An empty value means "follow the default target" (new categories start that way).
const targetSchema = z.object({
  value: z.union([z.literal(''), z.coerce.number<string | number>().int().positive().max(43_200)]),
  unit: z.enum(['min', 'h']),
});

const toMinutes = ({ value, unit }: { value: number | ''; unit: Unit }) => (value === '' ? 0 : unit === 'h' ? value * 60 : value);

function fromMinutes(minutes: number): { value: number; unit: Unit } {
  return minutes >= 60 && minutes % 60 === 0 ? { value: minutes / 60, unit: 'h' } : { value: minutes, unit: 'min' };
}

function useCategorySchema() {
  const { t } = useTranslation();
  const pair = z
    .object({ first: targetSchema, resolution: targetSchema })
    .refine((p) => p.first.value === '' || p.resolution.value === '' || toMinutes(p.resolution) >= toMinutes(p.first), {
      message: t('categories.resolutionBeforeResponse'),
      path: ['resolution', 'value'],
    });

  return z.object({
    name: z.string().trim().min(2, t('auth.required')).max(80),
    description: z.string().trim().max(500),
    sla: z.object({ urgent: pair, high: pair, normal: pair, low: pair }),
  });
}

type CategoryFormInput = z.input<ReturnType<typeof useCategorySchema>>;
type CategoryFormOutput = z.output<ReturnType<typeof useCategorySchema>>;

function defaultsFor(category: Category | null): CategoryFormInput {
  const sla = Object.fromEntries(
    PRIORITIES.map((priority) => {
      const target = category?.sla_targets[priority];

      return [
        priority,
        target
          ? { first: fromMinutes(target.first_response_minutes), resolution: fromMinutes(target.resolution_minutes) }
          : { first: { value: '', unit: 'min' as Unit }, resolution: { value: '', unit: 'h' as Unit } },
      ];
    }),
  ) as CategoryFormInput['sla'];

  return { name: category?.name ?? '', description: category?.description ?? '', sla };
}

function TargetField({
  id,
  label,
  register,
  error,
}: {
  id: string;
  label: string;
  register: (field: 'value' | 'unit') => object;
  error: string | undefined;
}) {
  const { t } = useTranslation();

  return (
    <div>
      <div className="flex items-center gap-1">
        <Input id={id} inputMode="numeric" aria-label={label} className="w-20 text-right font-mono" {...register('value')} />
        <NativeSelect aria-label={`${label} (${t('categories.minutes')}/${t('categories.hours')})`} {...register('unit')}>
          <option value="min">{t('categories.minutes')}</option>
          <option value="h">{t('categories.hours')}</option>
        </NativeSelect>
      </div>
      <FieldError>{error}</FieldError>
    </div>
  );
}

function CategoryEditor({ category, onSaved }: { category: Category | null; onSaved: (category: Category) => void }) {
  const { t } = useTranslation();
  const toast = useToast();
  const schema = useCategorySchema();
  const save = useSaveCategory();
  const {
    register,
    handleSubmit,
    setError,
    formState: { errors, dirtyFields },
  } = useForm<CategoryFormInput, unknown, CategoryFormOutput>({
    resolver: zodResolver(schema),
    defaultValues: defaultsFor(category),
  });

  const submit = (resetSla: boolean) =>
    handleSubmit((values) => {
      // Only send targets the admin set or changed; the rest keep following the defaults.
      const targets = PRIORITIES.filter(
        (priority) =>
          values.sla[priority].first.value !== '' &&
          values.sla[priority].resolution.value !== '' &&
          (category?.sla_targets[priority].is_custom === true || dirtyFields.sla?.[priority] !== undefined),
      );
      const input: CategoryInput = {
        name: values.name,
        description: values.description === '' ? null : values.description,
        ...(resetSla
          ? { sla_targets: null }
          : targets.length > 0
            ? {
                sla_targets: Object.fromEntries(
                  targets.map((priority) => [
                    priority,
                    {
                      first_response_minutes: toMinutes(values.sla[priority].first),
                      resolution_minutes: toMinutes(values.sla[priority].resolution),
                    },
                  ]),
                ),
              }
            : {}),
      };

      save.mutate(
        { id: category?.id ?? null, input },
        {
          onSuccess: (saved) => {
            toast.success(category ? t('categories.updated') : t('categories.created'));
            onSaved(saved);
          },
          onError: (error) => {
            if (error instanceof ApiError && error.status === 422) {
              const fields = error.fieldErrors();
              setError('name', { message: fields.name ?? fields.sla_targets ?? Object.values(fields)[0] ?? '' });
            } else {
              toast.error(t('common.genericError'));
            }
          },
        },
      );
    });

  const slaError = (priority: TicketPriority, part: 'first' | 'resolution', fieldErrors: FieldErrors<CategoryFormInput>) => {
    const error = fieldErrors.sla?.[priority]?.[part]?.value;

    return error
      ? error.message === t('categories.resolutionBeforeResponse')
        ? error.message
        : t('categories.invalidTarget')
      : undefined;
  };

  return (
    <form noValidate onSubmit={(event) => void submit(false)(event)} className="grid gap-5">
      <div className="grid gap-4 md:grid-cols-2">
        <div className="grid gap-1.5">
          <Label htmlFor="category-name">{t('categories.name')}</Label>
          <Input id="category-name" aria-invalid={errors.name ? true : undefined} {...register('name')} />
          <FieldError>{errors.name?.message}</FieldError>
        </div>
        <div className="grid gap-1.5 md:col-span-2">
          <Label htmlFor="category-description">{t('categories.description')}</Label>
          <Textarea
            id="category-description"
            rows={2}
            aria-describedby="category-description-hint"
            {...register('description')}
          />
          <p id="category-description-hint" className="text-xs text-muted-foreground">
            {t('categories.descriptionHint')}
          </p>
        </div>
      </div>

      <div className="overflow-x-auto rounded-lg border">
        <table className="w-full text-[13px]">
          <thead className="border-b bg-muted/40 text-left text-muted-foreground">
            <tr>
              <th className="px-3 py-2.5 font-medium">{t('categories.priority')}</th>
              <th className="px-3 py-2.5 font-medium">{t('categories.firstResponse')}</th>
              <th className="px-3 py-2.5 font-medium">{t('categories.resolution')}</th>
              <th className="px-3 py-2.5">
                <span className="sr-only">{t('categories.custom')}</span>
              </th>
            </tr>
          </thead>
          <tbody>
            {PRIORITIES.map((priority) => (
              <tr key={priority} className="border-b last:border-0">
                <td className="px-3 py-2.5">
                  <PriorityBadge priority={priority} />
                </td>
                <td className="px-3 py-2.5">
                  <TargetField
                    id={`sla-${priority}-first`}
                    label={`${t('categories.firstResponse')} · ${t(`priority.${priority}`)}`}
                    register={(field) => register(`sla.${priority}.first.${field}`)}
                    error={slaError(priority, 'first', errors)}
                  />
                </td>
                <td className="px-3 py-2.5">
                  <TargetField
                    id={`sla-${priority}-resolution`}
                    label={`${t('categories.resolution')} · ${t(`priority.${priority}`)}`}
                    register={(field) => register(`sla.${priority}.resolution.${field}`)}
                    error={slaError(priority, 'resolution', errors)}
                  />
                </td>
                <td className="px-3 py-2.5 text-right">
                  {category ? (
                    <Badge variant={category.sla_targets[priority].is_custom ? 'primary' : 'muted'}>
                      {category.sla_targets[priority].is_custom ? t('categories.custom') : t('categories.default')}
                    </Badge>
                  ) : null}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <div className="flex flex-wrap items-center gap-2">
        <p className="mr-auto text-xs text-muted-foreground">{t('categories.slaHint')}</p>
        {category ? (
          <Button variant="ghost" disabled={save.isPending} onClick={() => void submit(true)()}>
            {t('categories.resetSla')}
          </Button>
        ) : null}
        <Button type="submit" disabled={save.isPending}>
          {save.isPending ? t('common.saving') : t('categories.saveCategory')}
        </Button>
      </div>
    </form>
  );
}

function DeleteCategoryButton({ category, onDeleted }: { category: Category; onDeleted: () => void }) {
  const { t } = useTranslation();
  const toast = useToast();
  const remove = useDeleteCategory();
  const [open, setOpen] = useState(false);

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button variant="destructive" size="sm">
          {t('common.delete')}
        </Button>
      </DialogTrigger>
      <DialogContent title={t('categories.deleteTitle', { name: category.name })} description={t('categories.deleteText')}>
        <div className="flex justify-end gap-2">
          <DialogClose asChild>
            <Button variant="outline">{t('common.cancel')}</Button>
          </DialogClose>
          <Button
            variant="destructive"
            disabled={remove.isPending}
            onClick={() =>
              remove.mutate(category.id, {
                onSuccess: () => {
                  toast.success(t('categories.deleted'));
                  setOpen(false);
                  onDeleted();
                },
                onError: () => toast.error(t('common.genericError')),
              })
            }
          >
            {t('common.delete')}
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  );
}

export function CategoriesPage() {
  const { t } = useTranslation();
  const categories = useCategories();
  const [selected, setSelected] = useState<string | null>(null);

  const list = categories.data ?? [];
  const current = selected === 'new' ? null : (list.find((item) => item.id === selected) ?? list[0] ?? null);
  const creating = selected === 'new' || (categories.isSuccess && list.length === 0);

  return (
    <div>
      <PageHeader
        title={t('categories.title')}
        subtitle={t('categories.subtitle')}
        actions={
          <Button variant="outline" onClick={() => setSelected('new')}>
            <Plus />
            {t('categories.new')}
          </Button>
        }
      />
      {categories.isError ? (
        <ErrorState onRetry={() => void categories.refetch()} />
      ) : (
        <div className="grid gap-6 p-6 lg:grid-cols-[16rem_1fr]">
          <nav aria-label={t('categories.title')} className="grid content-start gap-1">
            {categories.isPending ? (
              <Skeleton className="h-12" />
            ) : list.length === 0 ? (
              <EmptyState title={t('categories.empty')} />
            ) : (
              list.map((item) => (
                <button
                  key={item.id}
                  type="button"
                  aria-current={!creating && current?.id === item.id ? 'true' : undefined}
                  onClick={() => setSelected(item.id)}
                  className={cn(
                    'rounded-md px-3 py-2 text-left hover:bg-muted',
                    !creating && current?.id === item.id && 'bg-muted',
                  )}
                >
                  <span className="block font-medium">{item.name}</span>
                  <span className="text-xs text-muted-foreground">
                    {t('categories.tickets', { count: item.tickets_count ?? 0 })}
                  </span>
                </button>
              ))
            )}
          </nav>

          <section aria-label={creating ? t('categories.new') : current?.name}>
            {categories.isPending ? (
              <Skeleton className="h-64" />
            ) : creating ? (
              <>
                <h2 className="mb-4 font-semibold">{t('categories.new')}</h2>
                <CategoryEditor key="new" category={null} onSaved={(saved) => setSelected(saved.id)} />
              </>
            ) : current ? (
              <>
                <div className="mb-4 flex items-center justify-between">
                  <h2 className="font-semibold">{current.name}</h2>
                  <DeleteCategoryButton category={current} onDeleted={() => setSelected(null)} />
                </div>
                <CategoryEditor key={current.id} category={current} onSaved={(saved) => setSelected(saved.id)} />
              </>
            ) : (
              <p className="text-muted-foreground">{t('categories.selectHint')}</p>
            )}
          </section>
        </div>
      )}
    </div>
  );
}
