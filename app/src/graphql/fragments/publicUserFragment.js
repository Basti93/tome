import gql from 'graphql-tag'

export const PUBLIC_USER_FRAGMENT = {
    publicUser: gql`
        fragment publicUsersFragment on User {
            firstName
            familyName
            groups {
                id
            }
        }
    `,
}
