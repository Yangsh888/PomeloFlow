import { themeModule } from '../modules/common/theme.js';
import { navigationModule } from '../modules/common/navigation.js';
import { searchModule } from '../modules/common/search.js';

function runModules(modules, ctx) {
  modules.forEach(module => {
    if (!module.guard || module.guard(ctx)) {
      try {
        module.init(ctx);
      } catch (error) {
        console.error('[PomeloFlow] module init failed:', module.name || 'unknown', error);
      }
    }
  });
}

async function loadAndRun(loaders, ctx) {
  const settled = await Promise.allSettled(loaders.map(loader => loader()));
  const modules = [];

  settled.forEach(result => {
    if (result.status !== 'fulfilled') {
      console.error('[PomeloFlow] module import failed:', result.reason);
      return;
    }

    const exported = result.value;
    if (typeof exported === 'function') {
      modules.push(exported());
    }
  });

  if (modules.length > 0) {
    runModules(modules, ctx);
  }
}

function bootstrap() {
  if (window.__PF_BOOTSTRAPPED__) return;
  window.__PF_BOOTSTRAPPED__ = true;
  const cfg = window.PF_CONFIG || {};
  const ctx = { config: cfg };
  const baseModules = [
    themeModule(),
    navigationModule(),
    searchModule()
  ];

  runModules(baseModules, ctx);

  if (document.querySelector('#pf-reading-bar, #pf-toc, #pf-post-content')) {
    const articleLoaders = [];
    if (document.querySelector('#pf-reading-bar')) {
      articleLoaders.push(() => import('../modules/article/progress.js').then(mod => mod.progressModule));
    }
    if (document.querySelector('#pf-toc') && document.querySelector('#pf-post-content')) {
      articleLoaders.push(() => import('../modules/article/toc.js').then(mod => mod.tocModule));
    }
    if (document.querySelector('#pf-post-content')) {
      articleLoaders.push(
        () => import('../modules/article/lightbox.js').then(mod => mod.lightboxModule),
        () => import('../modules/article/code-copy.js').then(mod => mod.codeCopyModule)
      );
    }
    if (articleLoaders.length > 0) {
      loadAndRun(articleLoaders, ctx);
    }
  }

  if (document.querySelector('#pf-comment-form, .pf-comment-reply-btn')) {
    loadAndRun([
      () => import('../modules/comment/typerenew-comment.js').then(mod => mod.commentModule)
    ], ctx);
  }

  if (document.querySelector('#pf-pomodoro, #pf-pom-float-panel')) {
    const focusLoaders = [];
    if (document.querySelector('#pf-pomodoro')) {
      focusLoaders.push(() => import('../modules/focus/pomodoro.js').then(mod => mod.pomodoroModule));
    }
    if (document.querySelector('#pf-pom-float-panel')) {
      focusLoaders.push(() => import('../modules/focus/float.js').then(mod => mod.floatPomodoroModule));
    }
    if (document.querySelector('#pf-music')
      && (cfg.musicEnable === true || cfg.musicEnable === 'true')
      && Array.isArray(cfg.musicTracks)
      && cfg.musicTracks.length > 0) {
      focusLoaders.push(() => import('../modules/focus/music.js').then(mod => mod.musicModule));
    }
    if (focusLoaders.length > 0) {
      loadAndRun(focusLoaders, ctx);
    }
  }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', bootstrap);
} else {
  bootstrap();
}
