import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import type { AIConfiguration } from '@/types/ai';

interface AIStoreState {
    configuration: AIConfiguration | null;
    provider: string;
    model: string;
    temperature: number;
    maxTokens: number;
    topP: number;
    streaming: boolean;
    language: string;
    timeoutSeconds: number;
    isActive: boolean;
    setConfiguration: (config: AIConfiguration) => void;
    setProvider: (provider: string) => void;
    setModel: (model: string) => void;
    setTemperature: (temperature: number) => void;
    setMaxTokens: (maxTokens: number) => void;
    setTopP: (topP: number) => void;
    setStreaming: (streaming: boolean) => void;
    setLanguage: (language: string) => void;
    setTimeoutSeconds: (timeoutSeconds: number) => void;
    setIsActive: (isActive: boolean) => void;
}

export const useAIStore = create<AIStoreState>()(
    persist(
        (set) => ({
            configuration: null,
            provider: 'openai',
            model: 'gpt-4o',
            temperature: 0.7,
            maxTokens: 2048,
            topP: 1,
            streaming: false,
            language: 'en',
            timeoutSeconds: 30,
            isActive: true,
            setConfiguration: (configuration) =>
                set({
                    configuration,
                    provider: configuration.provider,
                    model: configuration.model,
                    temperature: configuration.temperature,
                    maxTokens: configuration.max_tokens,
                    topP: configuration.top_p ?? 1,
                    streaming: configuration.streaming ?? false,
                    language: configuration.language ?? 'en',
                    timeoutSeconds: configuration.timeout_seconds ?? 30,
                    isActive: configuration.is_active ?? true,
                }),
            setProvider: (provider) => set({ provider }),
            setModel: (model) => set({ model }),
            setTemperature: (temperature) => set({ temperature }),
            setMaxTokens: (maxTokens) => set({ maxTokens }),
            setTopP: (topP) => set({ topP }),
            setStreaming: (streaming) => set({ streaming }),
            setLanguage: (language) => set({ language }),
            setTimeoutSeconds: (timeoutSeconds) => set({ timeoutSeconds }),
            setIsActive: (isActive) => set({ isActive }),
        }),
        {
            name: 'ai-store',
            partialize: (state) => ({
                provider: state.provider,
                model: state.model,
                temperature: state.temperature,
                maxTokens: state.maxTokens,
            }),
        },
    ),
);
