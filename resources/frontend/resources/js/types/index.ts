export interface DcodeUser {
  first_name?: string;
  middle_name?: string;
  last_name?: string;
  email: string;
}

export interface ResetPasswordPageProps {
  token: string;
  email?: string;
}
