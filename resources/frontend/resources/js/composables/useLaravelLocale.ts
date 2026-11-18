import { usePage } from "@inertiajs/vue3";

type TranslationTree = Record<string, unknown>;

export function useLaravelLocale() {
  const page = usePage<{ translations?: TranslationTree }>();

  const trans = (key: string, replacements: Record<string, string> = {}): string => {
    const value = key.split(".").reduce<unknown>((current, part) => {
      return current && typeof current === "object" ? (current as TranslationTree)[part] : undefined;
    }, page.props.translations);

    if (typeof value !== "string") {
      return key;
    }

    return Object.entries(replacements).reduce((result, [name, replacement]) => result.replace(`:${name}`, replacement), value);
  };

  return { trans };
}
