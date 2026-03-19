(function () {
    const portuguese = {
        weekdays: {
            shorthand: ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'],
            longhand: [
                'Domingo',
                'Segunda-feira',
                'Terça-feira',
                'Quarta-feira',
                'Quinta-feira',
                'Sexta-feira',
                'Sábado'
            ]
        },
        months: {
            shorthand: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
            longhand: [
                'Janeiro',
                'Fevereiro',
                'Março',
                'Abril',
                'Maio',
                'Junho',
                'Julho',
                'Agosto',
                'Setembro',
                'Outubro',
                'Novembro',
                'Dezembro'
            ]
        },
        firstDayOfWeek: 1,
        rangeSeparator: ' até ',
        time_24hr: true,
    };

    function localizeFlatpickr() {
        if (!window.flatpickr) return false;

        // 1) global (para novos datepickers)
        window.flatpickr.localize(portuguese);

        // 2) atualizar instâncias já criadas
        document.querySelectorAll('.flatpickr-input').forEach((el) => {
            const fp = el._flatpickr;
            if (!fp) return;

            try {
                fp.set('locale', portuguese); // aplica em instâncias existentes
                fp.redraw();
            } catch (e) {
                // ignore
            }
        });

        return true;
    }

    function applyWithRetries() {
        let tries = 0;
        const timer = setInterval(() => {
            tries++;
            if (localizeFlatpickr() || tries > 40) clearInterval(timer);
        }, 250);
    }

    // 1) primeiro load
    document.addEventListener('DOMContentLoaded', () => {
        if (!localizeFlatpickr()) applyWithRetries();
    });

    // 2) navegação interna do Filament/Livewire (muito importante!)
    document.addEventListener('livewire:navigated', () => {
        // pequeno delay para apanhar datepickers recém-criados
        setTimeout(() => localizeFlatpickr(), 50);
        setTimeout(() => localizeFlatpickr(), 300);
    });

    // 3) fallback: quando Livewire termina requests
    document.addEventListener('livewire:load', () => {
        setTimeout(() => localizeFlatpickr(), 50);
    });
})();