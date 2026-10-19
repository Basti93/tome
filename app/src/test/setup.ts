// Node >= 22 ships an experimental global `localStorage` which shadows jsdom's implementation
// and is unusable without --localstorage-file (it is `undefined` inside the jsdom environment).
// Install a small in-memory Storage so the tests behave the same on every Node version.
class MemoryStorage implements Storage {
  private data = new Map<string, string>()

  get length() {
    return this.data.size
  }

  clear() {
    this.data.clear()
  }

  getItem(key: string) {
    return this.data.has(key) ? (this.data.get(key) as string) : null
  }

  key(index: number) {
    return Array.from(this.data.keys())[index] ?? null
  }

  removeItem(key: string) {
    this.data.delete(key)
  }

  setItem(key: string, value: string) {
    this.data.set(key, String(value))
  }

  [name: string]: any
}

for (const name of ['localStorage', 'sessionStorage']) {
  Object.defineProperty(globalThis, name, { value: new MemoryStorage(), configurable: true, writable: true })
}
