export interface DcodeUser {
  name?: string;
  email: string;
}

export interface ResetPasswordPageProps {
  token: string;
  email?: string;
}
