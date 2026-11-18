import { usePage } from "@inertiajs/vue3";

type TranslationValue = string | Record<string, unknown>;

export function useDcodeTranslations() {
  const page = usePage<{ translations?: Record<string, TranslationValue> }>();
  const translate = (key: string, replacements: Record<string, string> = {}): string => {
    const value = key.split(".").reduce<unknown>((current, part) => (current && typeof current === "object" ? (current as Record<string, unknown>)[part] : undefined), page.props.translations);
    if (typeof value !== "string") return key;
    return Object.entries(replacements).reduce((result, [name, replacement]) => result.replace(`:${name}`, replacement), value);
  };
  return { translate };
}
