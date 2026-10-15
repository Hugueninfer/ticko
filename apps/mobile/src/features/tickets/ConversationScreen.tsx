import { ApiError, type Attachment, type TicketMessage } from '@ticko/api-client';
import { Image } from 'expo-image';
import { router, useLocalSearchParams } from 'expo-router';
import { ArrowUp, ChevronLeft, Plus } from 'lucide-react-native';
import { useMemo, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { FlatList, KeyboardAvoidingView, Platform, Pressable, StyleSheet, TextInput, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { AppText } from '@/components/AppText';
import { Button } from '@/components/Button';
import { ErrorState, LoadingList, OfflineBanner } from '@/components/States';
import { useCurrentUser } from '@/features/auth/AuthProvider';
import { isTicketId } from '@/lib/deeplink';
import { formatDayLabel, formatTime, ticketRef } from '@/lib/format';
import { pickPhoto } from '@/lib/images';
import { useOnline } from '@/lib/network';
import { useTicketUpdates } from '@/lib/realtime';
import { fonts, radius, space, useTheme } from '@/theme';

import { useAttachments, useChangeStatus, useMessages, useSendMessage, useTicket, useUploadPhoto } from './api';
import { StatusTimeline } from './StatusTimeline';
import { buildConversation, currentStep, type ConversationItem } from './timeline';

const MAX_MESSAGE = 20000;

export function ConversationScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();

  // Deep links are untrusted: anything but a ticket id is simply "not found".
  return isTicketId(id) ? <Conversation id={id.toLowerCase()} /> : <NotFound />;
}

function Header({ title, subtitle }: { title: string; subtitle?: string }) {
  const { t } = useTranslation();
  const { colors } = useTheme();

  return (
    <View style={[styles.header, { borderColor: colors.border }]}>
      <Pressable
        accessibilityRole="button"
        accessibilityLabel={t('conversation.back')}
        onPress={() => router.back()}
        hitSlop={12}
      >
        <ChevronLeft color={colors.primary} size={26} />
      </Pressable>
      <View style={styles.headerText}>
        <AppText variant="body" weight="semibold" numberOfLines={1} accessibilityRole="header">
          {title}
        </AppText>
        {subtitle ? (
          <AppText variant="small" color="mutedForeground" numberOfLines={1}>
            {subtitle}
          </AppText>
        ) : null}
      </View>
    </View>
  );
}

function NotFound() {
  const { t } = useTranslation();
  const { colors } = useTheme();

  return (
    <SafeAreaView style={[styles.safe, { backgroundColor: colors.background }]}>
      <Header title={t('conversation.notFound')} />
      <View style={styles.padded}>
        <ErrorState title={t('conversation.notFound')} hint={t('conversation.notFoundHint')} />
      </View>
    </SafeAreaView>
  );
}

function Conversation({ id }: { id: string }) {
  const { t, i18n } = useTranslation();
  const { colors } = useTheme();
  const user = useCurrentUser();
  const online = useOnline();
  const ticket = useTicket(id);
  const messages = useMessages(id);
  const attachments = useAttachments(id);
  const send = useSendMessage(id);
  const upload = useUploadPhoto(id);
  const changeStatus = useChangeStatus(id);
  const [draft, setDraft] = useState('');
  const [error, setError] = useState<string | null>(null);
  const list = useRef<FlatList<ConversationItem>>(null);

  useTicketUpdates(user.tenant?.id, id);

  const items = useMemo(() => buildConversation(messages.data ?? [], attachments.data ?? []), [messages.data, attachments.data]);

  if (ticket.error instanceof ApiError && (ticket.error.status === 404 || ticket.error.status === 403)) {
    return <NotFound />;
  }

  if (ticket.isPending || messages.isPending) {
    return (
      <SafeAreaView style={[styles.safe, { backgroundColor: colors.background }]}>
        <Header title="" />
        <View style={styles.padded}>
          <LoadingList rows={2} />
        </View>
      </SafeAreaView>
    );
  }

  if (!ticket.data) {
    return (
      <SafeAreaView style={[styles.safe, { backgroundColor: colors.background }]}>
        <Header title="" />
        <View style={styles.padded}>
          <ErrorState onRetry={() => void Promise.all([ticket.refetch(), messages.refetch()])} />
        </View>
      </SafeAreaView>
    );
  }

  const data = ticket.data;
  const closed = data.status === 'closed';
  const agent = data.assignee?.name.split(' ')[0];
  const subtitle = [
    t('tickets.reference', { ref: ticketRef(data.id) }),
    agent ? t('conversation.agentOf', { name: agent }) : null,
  ]
    .filter(Boolean)
    .join(' · ');

  const submit = async () => {
    const body = draft.trim();

    if (!body || send.isPending) return;

    setError(null);

    try {
      await send.mutateAsync(body);
      setDraft('');
      list.current?.scrollToEnd({ animated: true });
    } catch {
      setError(t('conversation.sendFailed'));
    }
  };

  const attach = async () => {
    setError(null);

    try {
      const result = await pickPhoto('gallery');

      if (result.kind === 'photo') {
        await upload.mutateAsync(result.photo);
      } else if (result.kind === 'denied') {
        setError(t('newTicket.permissionDenied'));
      }
    } catch {
      setError(t('common.genericError'));
    }
  };

  const renderItem = ({ item }: { item: ConversationItem }) => {
    if (item.kind === 'day') {
      return (
        <AppText variant="small" color="mutedForeground" style={styles.day}>
          {formatDayLabel(item.at, i18n.language, { today: t('common.today'), yesterday: t('common.yesterday') })}
        </AppText>
      );
    }

    if (item.kind === 'attachment') {
      return <AttachmentBubble attachment={item.attachment} mine={item.attachment.uploader_id === user.id} />;
    }

    return <MessageBubble message={item.message} mine={item.message.author.id === user.id} locale={i18n.language} />;
  };

  return (
    <SafeAreaView style={[styles.safe, { backgroundColor: colors.background }]}>
      <Header title={data.subject} subtitle={subtitle} />
      <OfflineBanner />
      <StatusTimeline step={currentStep(data)} />
      <KeyboardAvoidingView style={styles.safe} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
        <FlatList
          ref={list}
          data={items}
          keyExtractor={(item) => item.key}
          renderItem={renderItem}
          contentContainerStyle={styles.messages}
          onContentSizeChange={() => list.current?.scrollToEnd({ animated: false })}
          ListHeaderComponent={
            <View style={[styles.bubble, styles.mine, { backgroundColor: colors.primary }]}>
              <AppText style={{ color: colors.primaryForeground }}>{data.description}</AppText>
            </View>
          }
        />

        {error ? (
          <AppText variant="small" color="danger" style={styles.padded} accessibilityRole="alert">
            {error}
          </AppText>
        ) : null}

        {closed ? (
          <View style={[styles.closed, { borderColor: colors.border }]}>
            <AppText variant="small" color="mutedForeground">
              {t('conversation.closed')}
            </AppText>
            <Button
              label={t('conversation.reopen')}
              variant="outline"
              loading={changeStatus.isPending}
              disabled={!online}
              onPress={() => changeStatus.mutate('open')}
            />
          </View>
        ) : (
          <View style={[styles.composer, { borderColor: colors.border }]}>
            <Pressable
              accessibilityRole="button"
              accessibilityLabel={t('conversation.attach')}
              onPress={() => void attach()}
              disabled={!online || upload.isPending}
              hitSlop={8}
            >
              <Plus color={colors.mutedForeground} size={24} />
            </Pressable>
            <TextInput
              accessibilityLabel={t('conversation.placeholder')}
              placeholder={t('conversation.placeholder')}
              placeholderTextColor={colors.mutedForeground}
              value={draft}
              onChangeText={setDraft}
              maxLength={MAX_MESSAGE}
              multiline
              style={[styles.input, { borderColor: colors.border, color: colors.foreground }]}
            />
            <Pressable
              accessibilityRole="button"
              accessibilityLabel={t('conversation.send')}
              accessibilityState={{ disabled: !online || !draft.trim() || send.isPending }}
              disabled={!online || !draft.trim() || send.isPending}
              onPress={() => void submit()}
              style={[styles.send, { backgroundColor: colors.primary, opacity: !online || !draft.trim() ? 0.5 : 1 }]}
            >
              <ArrowUp color={colors.primaryForeground} size={22} />
            </Pressable>
          </View>
        )}
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

function MessageBubble({ message, mine, locale }: { message: TicketMessage; mine: boolean; locale: string }) {
  const { colors } = useTheme();

  return (
    <View style={[styles.messageBlock, mine ? styles.alignEnd : styles.alignStart]}>
      <View
        style={[
          styles.bubble,
          mine ? [styles.mine, { backgroundColor: colors.primary }] : { backgroundColor: colors.bubbleOther },
        ]}
      >
        <AppText style={{ color: mine ? colors.primaryForeground : colors.foreground }}>{message.body}</AppText>
      </View>
      <AppText variant="small" color="mutedForeground">
        {mine ? '' : `${message.author.name} · `}
        {formatTime(message.created_at, locale)}
      </AppText>
    </View>
  );
}

function AttachmentBubble({ attachment, mine }: { attachment: Attachment; mine: boolean }) {
  const { t } = useTranslation();
  const { colors } = useTheme();
  const ratio = attachment.width > 0 && attachment.height > 0 ? attachment.width / attachment.height : 4 / 3;

  return (
    <View style={[styles.messageBlock, mine ? styles.alignEnd : styles.alignStart]}>
      <View style={[styles.photo, { borderColor: colors.border, backgroundColor: colors.muted }]}>
        <Image
          // Signed URLs change on every refresh; cache by attachment id.
          source={{ uri: attachment.url, cacheKey: `attachment-${attachment.id}` }}
          style={{ width: '100%', aspectRatio: ratio }}
          contentFit="cover"
          accessibilityLabel={t('conversation.openAttachment', { name: attachment.original_name })}
        />
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1 },
  padded: { padding: space.xl },
  header: { flexDirection: 'row', alignItems: 'center', gap: space.md, paddingHorizontal: space.lg, paddingVertical: space.md },
  headerText: { flex: 1, gap: 2 },
  messages: { padding: space.lg, gap: space.md },
  day: { textAlign: 'center', marginVertical: space.sm },
  messageBlock: { gap: space.xs, maxWidth: '85%' },
  alignEnd: { alignSelf: 'flex-end', alignItems: 'flex-end' },
  alignStart: { alignSelf: 'flex-start', alignItems: 'flex-start' },
  bubble: { borderRadius: radius.mobile + 4, paddingHorizontal: space.lg, paddingVertical: space.md },
  mine: { alignSelf: 'flex-end' },
  photo: { width: 220, borderWidth: 1, borderRadius: radius.mobile, overflow: 'hidden' },
  composer: {
    flexDirection: 'row',
    alignItems: 'flex-end',
    gap: space.md,
    paddingHorizontal: space.lg,
    paddingVertical: space.md,
    borderTopWidth: 1,
  },
  input: {
    flex: 1,
    minHeight: 44,
    maxHeight: 140,
    borderWidth: 1,
    borderRadius: 22,
    paddingHorizontal: space.lg,
    paddingTop: 11,
    paddingBottom: 11,
    fontFamily: fonts.regular,
    fontSize: 16,
  },
  send: { width: 44, height: 44, borderRadius: 22, alignItems: 'center', justifyContent: 'center' },
  closed: { padding: space.lg, gap: space.md, borderTopWidth: 1 },
});
