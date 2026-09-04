'use client';

import { useEffect, useMemo, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { useInboxActions, useInboxConversations, useInboxWorkspace } from '@/hooks/useInbox';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';

export default function InboxPage() {
    const params = useParams();
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('');
    const [conversationId, setConversationId] = useState<number | null>(params.id ? Number(params.id) : null);
    const [reply, setReply] = useState('');
    const [note, setNote] = useState('');
    const [assigneeId, setAssigneeId] = useState('');

    const filters = useMemo(() => ({
        search: search || undefined,
        status: status || undefined,
        active: true,
        per_page: 50,
    }), [search, status]);

    const { data: listData } = useInboxConversations(filters);
    const { data: workspaceData } = useInboxWorkspace(conversationId);
    const actions = useInboxActions(conversationId);

    const conversations = listData?.data ?? [];
    const workspace = workspaceData?.data;

    useEffect(() => {
        if (!conversationId && conversations[0]) {
            setConversationId(conversations[0].id);
        }
    }, [conversationId, conversations]);

    useEffect(() => {
        if (conversationId) {
            actions.read.mutate();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [conversationId]);

    const selectedConversation = workspace?.conversation;

    return (
        <div className="h-[calc(100vh-4rem)] p-4">
            <div className="grid h-full grid-cols-[320px_minmax(0,1fr)] gap-4">
                <Card className="overflow-hidden">
                    <CardHeader className="space-y-3 border-b pb-4">
                        <div>
                            <CardTitle>Inbox</CardTitle>
                            <p className="text-sm text-muted-foreground">Shared AI + human collaboration workspace</p>
                        </div>
                        <div className="space-y-2">
                            <Input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Search conversations" />
                            <div className="flex gap-2">
                                {['', 'new', 'waiting', 'assigned', 'human_reply', 'resolved', 'closed'].map((value) => (
                                    <Button
                                        key={value || 'all'}
                                        size="sm"
                                        variant={status === value ? 'default' : 'outline'}
                                        onClick={() => setStatus(value)}
                                    >
                                        {value || 'All'}
                                    </Button>
                                ))}
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="max-h-[calc(100vh-14rem)] overflow-auto p-2">
                        <div className="space-y-2">
                                {conversations.length === 0 ? (
                                    <div className="flex flex-col items-center gap-2 px-4 py-12 text-center">
                                        <p className="text-sm font-medium">No conversations yet</p>
                                        <p className="max-w-sm text-sm text-muted-foreground">
                                            Your inbox fills up automatically once WhatsApp is
                                            connected and customers start messaging you. Connect your
                                            WhatsApp Business Account to begin receiving and replying to
                                            conversations here.
                                        </p>
                                        <Button asChild className="mt-2">
                                            <Link to="/whatsapp">Connect WhatsApp</Link>
                                        </Button>
                                    </div>
                                ) : (
                                    conversations.map((conversation) => (
                                    <button
                                        key={conversation.id}
                                        type="button"
                                        onClick={() => setConversationId(conversation.id)}
                                        className={`w-full rounded-lg border p-3 text-left transition ${conversationId === conversation.id ? 'border-primary bg-primary/5' : 'hover:bg-muted/50'}`}
                                    >
                                        <div className="flex items-center justify-between gap-2">
                                            <div className="min-w-0">
                                                <div className="flex items-center gap-2">
                                                    <span className="truncate font-medium">{conversation.title || conversation.customer?.name || 'Untitled conversation'}</span>
                                                    {conversation.is_pinned && <Badge variant="secondary">Pinned</Badge>}
                                                    {conversation.is_unread && <Badge>Unread</Badge>}
                                                </div>
                                                <p className="mt-1 truncate text-xs text-muted-foreground">{conversation.last_message_preview || conversation.last_message?.content || 'No recent messages'}</p>
                                            </div>
                                            <Badge variant="outline">{conversation.priority_label || conversation.priority}</Badge>
                                        </div>
                                    </button>
                                    ))
                                )}
                        </div>
                    </CardContent>
                </Card>

                <Card className="overflow-hidden">
                    {selectedConversation ? (
                        <div className="flex h-full flex-col">
                            <CardHeader className="border-b pb-4">
                                <div className="flex items-start justify-between gap-4">
                                    <div>
                                        <CardTitle>{selectedConversation.title || selectedConversation.customer?.name || 'Conversation'}</CardTitle>
                                        <p className="text-sm text-muted-foreground">{selectedConversation.status_label} · {selectedConversation.priority_label}</p>
                                    </div>
                                    <div className="flex flex-wrap gap-2">
                                        <Button size="sm" variant="outline" onClick={() => actions.takeover.mutate()}>Take over</Button>
                                        <Button size="sm" variant="outline" onClick={() => actions.release.mutate()}>Release</Button>
                                        <Button size="sm" variant="outline" onClick={() => actions.togglePin.mutate(!selectedConversation.is_pinned)}>
                                            {selectedConversation.is_pinned ? 'Unpin' : 'Pin'}
                                        </Button>
                                    </div>
                                </div>
                            </CardHeader>

                            <div className="grid flex-1 grid-cols-[minmax(0,1fr)_320px] gap-4 p-4">
                                <div className="flex min-h-0 flex-col gap-4">
                                    <div className="flex-1 overflow-auto rounded-lg border bg-background p-4">
                                        <div className="space-y-3">
                                            {(workspace?.messages ?? []).map((message) => (
                                                <div key={message.id} className={`max-w-[80%] rounded-2xl px-4 py-3 ${message.direction === 'outbound' ? 'ml-auto bg-primary text-primary-foreground' : 'bg-muted'}`}>
                                                    <p className="text-sm whitespace-pre-wrap">{message.content}</p>
                                                    <p className="mt-1 text-xs opacity-70">{new Date(message.created_at).toLocaleString()}</p>
                                                </div>
                                            ))}
                                        </div>
                                    </div>

                                    <div className="rounded-lg border p-4">
                                        <Label htmlFor="reply">Composer</Label>
                                        <Textarea id="reply" value={reply} onChange={(e) => setReply(e.target.value)} rows={4} placeholder="Type a reply or draft from AI suggestions" />
                                        <div className="mt-3 flex flex-wrap gap-2">
                                            <Button onClick={() => actions.sendMessage.mutate(reply)} disabled={!reply.trim()}>Send</Button>
                                            <Button variant="outline" onClick={() => actions.saveDraft.mutate(reply)} disabled={!reply.trim()}>Save draft</Button>
                                            <Button variant="outline" onClick={() => actions.deleteDraft.mutate()}>Clear draft</Button>
                                            <Button variant="secondary" onClick={() => actions.suggestions.mutate()}>AI suggestions</Button>
                                        </div>
                                    </div>
                                </div>

                                <div className="flex min-h-0 flex-col gap-4 overflow-auto pr-1">
                                    <Card>
                                        <CardHeader className="pb-3">
                                            <CardTitle className="text-base">Customer</CardTitle>
                                        </CardHeader>
                                        <CardContent className="space-y-2 text-sm">
                                            <div><span className="text-muted-foreground">Name:</span> {selectedConversation.customer?.name || '-'}</div>
                                            <div><span className="text-muted-foreground">Phone:</span> {selectedConversation.customer?.phone || '-'}</div>
                                            <div><span className="text-muted-foreground">Email:</span> {selectedConversation.customer?.email || '-'}</div>
                                            <div><span className="text-muted-foreground">Assigned:</span> {selectedConversation.assigned_to?.name || 'Unassigned'}</div>
                                        </CardContent>
                                    </Card>

                                    <Card>
                                        <CardHeader className="pb-3">
                                            <CardTitle className="text-base">Assignment</CardTitle>
                                        </CardHeader>
                                        <CardContent className="space-y-3">
                                            <Input value={assigneeId} onChange={(e) => setAssigneeId(e.target.value)} placeholder="Agent user ID" />
                                            <div className="flex gap-2">
                                                <Button size="sm" onClick={() => actions.assign.mutate({ userId: Number(assigneeId) })} disabled={!assigneeId.trim()}>Assign</Button>
                                                <Button size="sm" variant="outline" onClick={() => actions.updateStatus.mutate('resolved')}>Resolve</Button>
                                            </div>
                                        </CardContent>
                                    </Card>

                                    <Card>
                                        <CardHeader className="pb-3">
                                            <CardTitle className="text-base">Internal note</CardTitle>
                                        </CardHeader>
                                        <CardContent className="space-y-3">
                                            <Textarea value={note} onChange={(e) => setNote(e.target.value)} rows={4} placeholder="Private note for the team" />
                                            <Button size="sm" variant="outline" onClick={() => actions.addNote.mutate(note)} disabled={!note.trim()}>Add note</Button>
                                        </CardContent>
                                    </Card>

                                    <Card>
                                        <CardHeader className="pb-3">
                                            <CardTitle className="text-base">AI Suggestions</CardTitle>
                                        </CardHeader>
                                        <CardContent>
                                            {workspace?.typing?.is_typing && <p className="mb-2 text-xs text-muted-foreground">Someone is typing...</p>}
                                            {actions.suggestions.data?.data && (
                                                <Tabs defaultValue="reply">
                                                    <TabsList className="grid grid-cols-2">
                                                        <TabsTrigger value="reply">Reply</TabsTrigger>
                                                        <TabsTrigger value="alternatives">Alternatives</TabsTrigger>
                                                    </TabsList>
                                                    <TabsContent value="reply" className="space-y-3 pt-3">
                                                        <div className="rounded-md border bg-muted/40 p-3 text-sm whitespace-pre-wrap">{actions.suggestions.data.data.suggested_reply}</div>
                                                        <div className="flex flex-wrap gap-2">
                                                            <Badge>{actions.suggestions.data.data.decision.decision}</Badge>
                                                            <Badge variant="outline">{(actions.suggestions.data.data.decision.confidence * 100).toFixed(0)}%</Badge>
                                                        </div>
                                                        <Button size="sm" onClick={() => setReply(actions.suggestions.data!.data.suggested_reply)}>Use reply</Button>
                                                    </TabsContent>
                                                    <TabsContent value="alternatives" className="space-y-2 pt-3">
                                                        {actions.suggestions.data.data.alternative_replies.map((alt) => (
                                                            <button key={alt.label} type="button" onClick={() => setReply(alt.content)} className="w-full rounded-md border p-3 text-left text-sm hover:bg-muted/50">
                                                                <div className="mb-1 font-medium">{alt.label}</div>
                                                                <div className="line-clamp-3 text-muted-foreground">{alt.content}</div>
                                                            </button>
                                                        ))}
                                                    </TabsContent>
                                                </Tabs>
                                            )}
                                        </CardContent>
                                    </Card>

                                    <Card>
                                        <CardHeader className="pb-3">
                                            <CardTitle className="text-base">Draft manager</CardTitle>
                                        </CardHeader>
                                        <CardContent className="space-y-2 text-sm">
                                            {(workspace?.drafts ?? []).length ? workspace?.drafts.map((item) => (
                                                <button
                                                    key={item.id}
                                                    type="button"
                                                    onClick={() => setReply(item.content)}
                                                    className="w-full rounded-md border p-3 text-left hover:bg-muted/50"
                                                >
                                                    <div className="flex items-center justify-between gap-2">
                                                        <span className="font-medium">Draft #{item.id}</span>
                                                        <span className="text-xs text-muted-foreground">{new Date(item.updated_at).toLocaleString()}</span>
                                                    </div>
                                                    <p className="mt-2 line-clamp-3 text-muted-foreground">{item.content}</p>
                                                </button>
                                            )) : <p className="text-muted-foreground">No drafts saved yet.</p>}
                                        </CardContent>
                                    </Card>
                                </div>
                            </div>
                        </div>
                    ) : (
                        <div className="flex h-full items-center justify-center text-muted-foreground">Select a conversation to open the workspace.</div>
                    )}
                </Card>
            </div>
        </div>
    );
}