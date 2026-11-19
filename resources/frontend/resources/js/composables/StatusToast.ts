import { DsgAlert } from "@dsg";

export type FlashStatus = string | {
  title: string;
  message: string;
  type?: "info" | "success" | "warning" | "error";
};

export const mountedStatusToast = (status?: FlashStatus): void => {
  if (!status) {
    return;
  }

  if (typeof status === "string") {
    DsgAlert.info(status);
    return;
  }

  const type = status.type ?? "info";
  DsgAlert[type](status.title, status.message);
};
