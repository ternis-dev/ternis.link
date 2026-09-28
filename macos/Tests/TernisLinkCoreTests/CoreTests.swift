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

@Test func invalidAPIKeyThrowsBeforeKeychain() async {
    await #expect(throws: AuthManager.SignInError.self) {
        try await AuthManager().signInWithAPIKey("not-a-key")
    }
}

@Test @MainActor func historyRoundTripsAndCaps() {
    let dir = FileManager.default.temporaryDirectory.appending(path: UUID().uuidString, directoryHint: .isDirectory)
    try! FileManager.default.createDirectory(at: dir, withIntermediateDirectories: true)
    defer { try? FileManager.default.removeItem(at: dir) }

    let store = HistoryStore(directory: dir)
    #expect(store.entries.isEmpty)
    store.add(shortUrl: "https://href.nz/abc", destination: "https://example.com")
    #expect(store.entries.count == 1)
    #expect(store.entries.first?.shortUrl == "https://href.nz/abc")

    // Reload from disk.
    let reloaded = HistoryStore(directory: dir)
    #expect(reloaded.entries.count == 1)

    reloaded.clear()
    #expect(reloaded.entries.isEmpty)
}

@Test func shortUrlBuildsFromDomainAndSlug() {
    let json = """
        {"id":"01J","slug":"abc123","destination_url":"https://example.com",\
        "domain":{"id":"02D","hostname":"href.nz"}}
        """.data(using: .utf8)!
    let link = try! JSONDecoder.api.decode(APILink.self, from: json)
    #expect(link.shortUrl?.absoluteString == "https://href.nz/abc123")
}

@Test func linksPageDecodesWithClickCounts() {
    let json = """
        {"data":[{"id":"01J","slug":"abc","destination_url":"https://example.com",\
        "description":"Docs","tags":["docs"],"is_active":true,"click_count":42,\
        "created_at":"2026-09-20T10:00:00.000000Z",\
        "domain":{"id":"02D","hostname":"href.nz"}}],\
        "current_page":1,"last_page":3,"total":55}
        """.data(using: .utf8)!
    let page = try! JSONDecoder.api.decode(Paged<APILink>.self, from: json)
    #expect(page.total == 55)
    #expect(page.lastPage == 3)
    #expect(page.data.first?.clickCount == 42)
    #expect(page.data.first?.tags == ["docs"])
    #expect(page.data.first?.createdAt != nil)
}

@Test func clickSummaryDecodesAllBreakdowns() {
    let json = """
        {"total_clicks":100,"unique_visitors":70,\
        "top_referrers":[{"referrer":"https://news.example","count":40}],\
        "top_countries":[{"country_code":"DE","count":60}],\
        "clicks_by_day":[{"date":"2026-09-27","count":30},{"date":"2026-09-28","count":70}]}
        """.data(using: .utf8)!
    let summary = try! JSONDecoder.api.decode(ClickSummary.self, from: json)
    #expect(summary.totalClicks == 100)
    #expect(summary.topReferrers.first?.label == "https://news.example")
    #expect(summary.topCountries.first?.label == "DE")
    #expect(summary.clicksByDay.count == 2)
    #expect(summary.clicksByDay.last?.day != nil)
    #expect(DayCount(date: "not-a-date", count: 1).day == nil)
}

// MARK: - M3 account models

@Test func apiKeyCreateResponseDecodesRawTokenOnce() {
    let json = """
        {"id":"01J","name":"Mac","key_prefix":"tl_abc123","api_version":1,\
        "last_used_at":null,"revoked_at":null,\
        "created_at":"2026-09-28T10:00:00.000000Z","api_key":"tl_rawtoken"}
        """.data(using: .utf8)!
    let key = try! JSONDecoder.api.decode(APIKey.self, from: json)
    #expect(key.displayName == "Mac (tl_abc123…)")
    #expect(key.apiKey == "tl_rawtoken")
    #expect(!key.isRevoked)
    #expect(key.createdAt != nil)
}

@Test func apiKeyListHidesRawToken() {
    let json = """
        {"data":[{"id":"01J","name":"Mac","key_prefix":"tl_abc123","api_version":1,\
        "revoked_at":"2026-09-28T11:00:00.000000Z",\
        "created_at":"2026-09-28T10:00:00.000000Z"}],\
        "current_page":1,"last_page":1,"total":1}
        """.data(using: .utf8)!
    let page = try! JSONDecoder.api.decode(Paged<APIKey>.self, from: json)
    #expect(page.data.first?.isRevoked == true)
    #expect(page.data.first?.apiKey == nil)
}

@Test func notificationDecodesPayload() {
    let json = """
        {"data":[{"id":"01N","type":"App.Notifications.SecurityAlert",\
        "data":{"title":"New API key created","lines":["A key was created."],\
        "action_url":"https://dash.ternis.link/api-keys","action_label":"View"},\
        "read_at":null,"created_at":"2026-09-28T10:00:00.000000Z"}],\
        "current_page":1,"last_page":1,"total":1}
        """.data(using: .utf8)!
    let page = try! JSONDecoder.api.decode(Paged<AppNotification>.self, from: json)
    let item = page.data.first!
    #expect(item.isUnread)
    #expect(item.title == "New API key created")
    #expect(item.data?.body == "A key was created.")
}

@Test func activityDecodesActorAndMetadata() {
    let json = """
        {"data":[{"id":"01A","actor_id":"01U","action":"api_key.created",\
        "subject_type":"App.Models.ApiKey","subject_id":"01K",\
        "subject_owner_id":"01U","subject_label":null,\
        "metadata":{"name":"Mac","via":"api","count":1},\
        "created_at":"2026-09-28T10:00:00.000000Z",\
        "actor":{"id":"01U","name":"Jane","email":"jane@example.com"}}],\
        "current_page":1,"last_page":1,"total":1}
        """.data(using: .utf8)!
    let page = try! JSONDecoder.api.decode(Paged<ActivityEntry>.self, from: json)
    let entry = page.data.first!
    #expect(entry.actorName == "Jane")
    #expect(entry.metadata?["via"]?.stringValue == "api")
    #expect(entry.metadata?["count"]?.stringValue == "1")
}

@Test func settingsRoundTripSnakeCase() {
    let json = """
        {"nav_layout":"top","theme":"dark","notify_security_email":false,\
        "notify_admin_security_email":true,"notify_server_error_email":true}
        """.data(using: .utf8)!
    let settings = try! JSONDecoder.api.decode(UserSettings.self, from: json)
    #expect(settings.navLayout == "top")
    #expect(settings.theme == "dark")
    #expect(!settings.notifySecurityEmail)

    let patch = SettingsPatch(theme: "light")
    let encoded = try! JSONEncoder.api.encode(patch)
    let body = String(data: encoded, encoding: .utf8)!
    #expect(body.contains("light"))
}
