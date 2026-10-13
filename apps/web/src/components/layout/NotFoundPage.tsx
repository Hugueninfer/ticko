import { useTranslation } from 'react-i18next';
import { Link } from 'react-router';

import { Button } from '@/components/ui/button';

export function NotFoundPage() {
  const { t } = useTranslation();

  return (
    <div className="flex flex-col items-start gap-3 p-6">
      <p className="text-lg font-semibold">{t('errors.notFound')}</p>
      <Button asChild variant="outline">
        <Link to="/tickets">{t('errors.goHome')}</Link>
      </Button>
    </div>
  );
}
