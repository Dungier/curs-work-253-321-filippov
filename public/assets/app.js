document.querySelectorAll('form[data-confirm]').forEach(form => {
  form.addEventListener('submit', event => {
    if (!window.confirm(form.dataset.confirm)) {
      event.preventDefault();
    }
  });
});

const newStoryForm = document.querySelector('#new-story-form');

if (newStoryForm) {
  newStoryForm.addEventListener('submit', () => {
    newStoryForm.querySelector('button[type="submit"]').disabled = true;
  });
}

const generation = document.querySelector('[data-generation]');

if (generation) {
  const label = document.querySelector('#progress-label');
  const fill = document.querySelector('#progress-fill');
  const progress = document.querySelector('[role="progressbar"]');
  const error = document.querySelector('#generation-error');
  const retry = document.querySelector('#generation-retry');
  const status = document.querySelector('#generation-status');
  let current = Number(generation.dataset.current);
  const total = Number(generation.dataset.total);
  let running = false;

  function update() {
    fill.style.width = `${current / total * 100}%`;
    progress.setAttribute('aria-valuenow', current);
    label.textContent = `Готово страниц: ${current} из ${total}`;
  }

  async function write() {
    if (running) {
      return;
    }

    running = true;
    retry.hidden = true;
    error.hidden = true;
    status.textContent = 'Создаём следующую страницу. Это может занять около минуты.';

    try {
      while (current < total) {
        const body = new URLSearchParams({ csrf: generation.dataset.csrf });
        const response = await fetch(generation.dataset.generation, { method: 'POST', body, headers: { Accept: 'application/json' } });
        const data = await response.json();

        if (!response.ok) {
          throw new Error(data.error || 'Не удалось создать страницу. Попробуйте ещё раз.');
        }

        current = Number(data.current);
        update();

        if (data.done) {
          status.textContent = 'История готова. Открываем её…';
          window.location.assign(data.url);
          return;
        }
      }
    } catch (failure) {
      error.textContent = failure instanceof SyntaxError ? 'Связь с сервером прервалась. Уже готовые страницы сохранены.' : failure.message;
      error.hidden = false;
      retry.hidden = false;
      status.textContent = 'Можно продолжить с того места, где остановились.';
    } finally {
      running = false;
    }
  }

  retry.addEventListener('click', write);
  update();

  if (error.textContent.trim()) {
    retry.hidden = false;
    status.textContent = 'Можно продолжить создание истории.';
  } else {
    write();
  }
}
