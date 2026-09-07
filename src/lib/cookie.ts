import Cookies from 'js-cookie'

export const cookie = Cookies.withAttributes({
  path: '/',
  secure: import.meta.env.PROD,
})
