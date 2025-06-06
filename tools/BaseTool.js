export class BaseTool {
    constructor() {
        if (this.constructor === BaseTool) {
            throw new Error(
                "BaseTool is an abstract class and cannot be instantiated directly"
            );
        }
    }

    get name() {
        throw new Error("name() must be implemented");
    }

    get description() {
        throw new Error("description() must be implemented");
    }

    get inputSchema() {
        throw new Error("inputSchema() must be implemented");
    }

    async execute(params) {
        throw new Error("execute() must be implemented");
    }

    validateInput(input) {
        const schema = this.inputSchema;
        // 这里可以使用 Joi 或其他验证库进行输入验证
        return true;
    }
}
