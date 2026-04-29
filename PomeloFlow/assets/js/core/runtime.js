export class EventScope {
  on(target, type, listener, options = false) {
    if (!target || !target.addEventListener) return;
    target.addEventListener(type, listener, options);
  }
}
