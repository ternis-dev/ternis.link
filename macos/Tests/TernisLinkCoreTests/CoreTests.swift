import Foundation
import Testing
@testable import TernisLinkCore

// MARK: - PKCE (RFC 7636 Appendix B test vector)

@Test func pkceChallengeMatchesRFCVector() {
    let verifier = "dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk"
    #expect(PKCE.challenge(for: verifier) == "E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM")
}

@Test func pkceVerifierIsURLSafe() {
    let verifier = PKCE.makeVerifier()
    #expect(verifier.count >= 43)
    #expect(verifier.range(of: "^[A-Za-z0-9_-]+$", options: .regularExpression) != nil)
}

// MARK: - SSO scheme parsing

@Test func ssoSchemeParsedFromRedirectURI() {
    #expect(SSOAuthorizer.scheme(of: "ternislink://oauth/callback") == "ternislink")
}

// MARK: - API error mapping

@Test func rateLimitParsesRetryAfter() {
    let stringHeader = APIClient.mapError(status: 429, headers: ["Retry-After": "120"], data: Data())
    guard case .rateLimited(let seconds) = stringHeader else {
        Issue.record("expected .rateLimited")
        return
    }
    #expect(seconds == 120)

    let intHeader = APIClient.mapError(status: 429, headers: ["Retry-After": 30], data: Data())
    guard case .rateLimited(let seconds2) = intHeader else {
        Issue.record("expected .rateLimited")
        return
    }
    #expect(seconds2 == 30)
}

@Test func validationSurfacesFirstMessage() {
    let data = """
        {"message":"The slug is taken.","errors":{"slug":["This slug is already taken."]}}
        """.data(using: .utf8)!
    let error = APIClient.mapError(status: 422, headers: [:], data: data)
    #expect(error.userMessage == "This slug is already taken.")
}

@Test func retiredVersionMaps() {
    let data = #"{"message":"Retired.","latest_version":2}"#.data(using: .utf8)!
    let error = APIClient.mapError(status: 410, headers: [:], data: data)
    guard case .versionRetired(let latest) = error else {
        Issue.record("expected .versionRetired")
        return
    }
    #expect(latest == 2)
}

// MARK: - Models & credentials

@Test func emptySlugNormalizesToNil() {
    let request = CreateLinkRequest(destinationUrl: "https://example.com", domainId: "abc", slug: "")
    #expect(request.slug == nil)
}

@Test func expiredTokensNeedRefresh() {
    let tokens = TokenSet(accessToken: "a", refreshToken: "r", expiresIn: -10)
    #expect(tokens.isExpired)
    #expect(Credential.sso(tokens).bearerIfFresh == nil)
    #expect(Credential.apiKey("tl_abc").bearerIfFresh == "tl_abc")
}

@Test func shortURLBuildsFromDomainAndSlug() {
    let json = """
        {"id":"01J","slug":"abc123","destination_url":"https://example.com",\
        "domain":{"id":"02D","hostname":"href.nz"}}
        """.data(using: .utf8)!
    let link = try! JSONDecoder.api.decode(APILink.self, from: json)
    #expect(link.shortURL?.absoluteString == "https://href.nz/abc123")
}
