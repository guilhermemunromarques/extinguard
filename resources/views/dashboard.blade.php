<x-layouts::app title="Painel">
    <div class="flex w-full flex-1 flex-col gap-8">
        <div class="flex flex-col gap-2">
            <flux:heading size="xl">Olá, {{ auth()->user()->name }}</flux:heading>
            <flux:text>Visão geral do gerenciamento de extintores.</flux:text>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <flux:card class="flex flex-col gap-2">
                <flux:text>Extintores</flux:text>
                <flux:heading size="lg">Prontos para consulta</flux:heading>
                <flux:text size="sm">Acompanhe equipamentos, reservas e posições físicas.</flux:text>
            </flux:card>

            <flux:card class="flex flex-col gap-2">
                <flux:text>Inspeções</flux:text>
                <flux:heading size="lg">Histórico completo</flux:heading>
                <flux:text size="sm">Registre e consulte as verificações realizadas.</flux:text>
            </flux:card>

            <flux:card class="flex flex-col gap-2">
                <flux:text>Plantas</flux:text>
                <flux:heading size="lg">Posições físicas</flux:heading>
                <flux:text size="sm">Visualize os equipamentos em seus andares.</flux:text>
            </flux:card>

            <flux:card class="flex flex-col gap-2">
                <flux:text>Sincronização</flux:text>
                <flux:heading size="lg">Pronta para uso</flux:heading>
                <flux:text size="sm">O trabalho de campo poderá continuar offline.</flux:text>
            </flux:card>
        </div>

        <flux:card class="flex min-h-64 flex-col justify-between gap-6">
            <div class="flex flex-col gap-2">
                <flux:heading size="lg">Comece pelo painel</flux:heading>
                <flux:text>Use a navegação lateral para acessar as áreas disponíveis para sua conta.</flux:text>
            </div>
            <div class="flex items-center gap-3 text-sm text-zinc-500 dark:text-zinc-400">
                <flux:icon name="shield-check" class="size-5" />
                <span>Seu acesso é controlado por permissões.</span>
            </div>
        </flux:card>
    </div>
</x-layouts::app>
