export class BaseTool {
    get name() {
        throw new Error("Method not implemented.");
    }

    get description() {
        throw new Error("Method not implemented.");
    }

    get inputSchema() {
        throw new Error("Method not implemented.");
    }

    validateInput(input) {
        throw new Error("Method not implemented.");
    }

    async execute(params) {
        throw new Error("Method not implemented.");
    }
}
