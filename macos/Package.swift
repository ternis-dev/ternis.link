// swift-tools-version: 6.0
import PackageDescription

let package = Package(
    name: "TernisLink",
    platforms: [.macOS(.v14)],
    products: [
        .executable(name: "TernisLink", targets: ["TernisLinkApp"]),
        .library(name: "TernisLinkCore", targets: ["TernisLinkCore"]),
    ],
    targets: [
        .target(
            name: "TernisLinkCore",
            swiftSettings: [.swiftLanguageMode(.v6)]
        ),
        .executableTarget(
            name: "TernisLinkApp",
            dependencies: ["TernisLinkCore"],
            swiftSettings: [.swiftLanguageMode(.v6)]
        ),
        .testTarget(
            name: "TernisLinkCoreTests",
            dependencies: ["TernisLinkCore"]
        ),
    ]
)
