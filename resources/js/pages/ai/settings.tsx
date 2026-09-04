'use client';
import { Settings2, Brain, Activity } from 'lucide-react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Slider } from '@/components/ui/slider';
import { Switch } from '@/components/ui/switch';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useAIConfiguration, useUpdateAIConfiguration, useAvailableProviders } from '@/hooks/useAI';
import { useAIStore } from '@/stores/ai-store';

export default function AISettingsPage() {
    useAIConfiguration();
    const { data: providersData } = useAvailableProviders();
    const updateConfig = useUpdateAIConfiguration();

    const {
        provider,
        model,
        temperature,
        maxTokens,
        topP,
        streaming,
        language,
        timeoutSeconds,
        isActive,
        setProvider,
        setModel,
        setTemperature,
        setMaxTokens,
        setTopP,
        setStreaming,
        setLanguage,
        setTimeoutSeconds,
        setIsActive,
    } = useAIStore();

    const handleSave = async () => {
        try {
            await updateConfig.mutateAsync({
                provider,
                model,
                temperature,
                top_p: topP,
                max_tokens: maxTokens,
                timeout_seconds: timeoutSeconds,
                streaming,
                language,
                is_active: isActive,
            });
            toast.success('AI configuration updated');
        } catch {
            toast.error('Failed to update configuration');
        }
    };

    const availableModels = providersData?.data?.[provider]?.models ?? [];

    return (
        <div className="container mx-auto space-y-6 p-6">
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-bold">AI Settings</h1>
                    <p className="text-muted-foreground">
                        Configure your AI provider and model preferences
                    </p>
                </div>
                <Button onClick={handleSave} disabled={updateConfig.isPending}>
                    <Settings2 className="mr-2 size-4" />
                    {updateConfig.isPending ? 'Saving...' : 'Save Configuration'}
                </Button>
            </div>

            <Tabs defaultValue="provider" className="space-y-6">
                <TabsList>
                    <TabsTrigger value="provider">Provider & Model</TabsTrigger>
                    <TabsTrigger value="parameters">Parameters</TabsTrigger>
                    <TabsTrigger value="advanced">Advanced</TabsTrigger>
                </TabsList>

                <TabsContent value="provider" className="space-y-4">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Brain className="size-5" />
                                AI Provider
                            </CardTitle>
                            <CardDescription>
                                Select your preferred AI provider and model
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label>Provider</Label>
                                <Select value={provider} onValueChange={setProvider}>
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {providersData?.data &&
                                            Object.entries(providersData.data).map(
                                                ([slug, info]) => (
                                                    <SelectItem key={slug} value={slug}>
                                                        <span className="flex items-center gap-2">
                                                            <span
                                                                className={`size-2 rounded-full ${info.healthy ? 'bg-green-500' : 'bg-red-500'}`}
                                                            />
                                                            {info.name}
                                                        </span>
                                                    </SelectItem>
                                                ),
                                            )}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-2">
                                <Label>Model</Label>
                                <Select value={model} onValueChange={setModel}>
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {availableModels.map((m: { id: string; name: string }) => (
                                            <SelectItem key={m.id} value={m.id}>
                                                {m.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="parameters" className="space-y-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>Generation Parameters</CardTitle>
                            <CardDescription>Fine-tune the AI response generation</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            <div className="space-y-2">
                                <Label>Temperature: {temperature}</Label>
                                <Slider
                                    value={[temperature]}
                                    onValueChange={([v]) => setTemperature(v)}
                                    min={0}
                                    max={2}
                                    step={0.1}
                                />
                                <p className="text-xs text-muted-foreground">
                                    Lower values produce more deterministic responses
                                </p>
                            </div>

                            <div className="space-y-2">
                                <Label>Max Tokens: {maxTokens}</Label>
                                <Slider
                                    value={[maxTokens]}
                                    onValueChange={([v]) => setMaxTokens(v)}
                                    min={64}
                                    max={8192}
                                    step={64}
                                />
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="advanced" className="space-y-4">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Activity className="size-5" />
                                Advanced Settings
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            <div className="space-y-2">
                                <Label>Top P: {topP}</Label>
                                <Slider
                                    value={[topP]}
                                    onValueChange={([v]) => setTopP(v)}
                                    min={0}
                                    max={1}
                                    step={0.05}
                                />
                                <p className="text-xs text-muted-foreground">
                                    Nucleus sampling threshold for response variety
                                </p>
                            </div>

                            <div className="flex items-center justify-between">
                                <div>
                                    <Label>Streaming</Label>
                                    <p className="text-xs text-muted-foreground">
                                        Enable streaming responses
                                    </p>
                                </div>
                                <Switch checked={streaming} onCheckedChange={setStreaming} />
                            </div>

                            <div className="flex items-center justify-between">
                                <div>
                                    <Label>Active</Label>
                                    <p className="text-xs text-muted-foreground">
                                        Use this configuration for AI generation
                                    </p>
                                </div>
                                <Switch checked={isActive} onCheckedChange={setIsActive} />
                            </div>

                            <div className="space-y-2">
                                <Label>Language</Label>
                                <Select value={language} onValueChange={setLanguage}>
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="en">English</SelectItem>
                                        <SelectItem value="es">Spanish</SelectItem>
                                        <SelectItem value="fr">French</SelectItem>
                                        <SelectItem value="de">German</SelectItem>
                                        <SelectItem value="pt">Portuguese</SelectItem>
                                        <SelectItem value="sw">Swahili (Kiswahili)</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-2">
                                <Label>Timeout (seconds)</Label>
                                <Input
                                    type="number"
                                    value={timeoutSeconds}
                                    onChange={(e) => setTimeoutSeconds(Number(e.target.value))}
                                    min={5}
                                    max={300}
                                />
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>
        </div>
    );
}
