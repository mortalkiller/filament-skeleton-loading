import { expect, test } from '@playwright/test'

// The Invoices page loads its table with the page, then each update (page, search, filter, sort,
// tab) waits WORKBENCH_LATENCY_MS on the server. The Workbench panel turns on
// `showSkeletonsOnTableUpdate()` and the shimmer; WORKBENCH_TABLE_UPDATE_SKELETONS=0 and
// WORKBENCH_SHIMMER=0 run the same tests against the panel without them.

const updateSkeletons = process.env.WORKBENCH_TABLE_UPDATE_SKELETONS !== '0'
const shimmer = process.env.WORKBENCH_SHIMMER !== '0'

const content = (page) => page.locator('.fi-ta-content-ctn')

const openInvoices = async (page) => {
    await page.goto('/admin/invoices')
    await expect(page.getByText('INV-2001', { exact: true })).toBeVisible({ timeout: 20000 })
}

// Watch the table while the update runs: Filament adds `fi-ta-content-loading` after its loading delay.
const duringUpdate = async (page, update) => {
    const loading = page.waitForFunction(
        () => document.querySelector('.fi-ta-content-ctn')?.classList.contains('fi-ta-content-loading'),
        null,
        { timeout: 5000 },
    ).then(() => true, () => false)

    await update()

    const shown = await loading
    await expect(content(page)).not.toHaveClass(/fi-ta-content-loading/, { timeout: 20000 })

    return shown
}

const updates = {
    'changing page': (page) => page.getByRole('button', { name: 'Next' }).click(),
    'searching': (page) => page.getByRole('searchbox').fill('Customer B'),
    'filtering': async (page) => {
        await page.getByRole('button', { name: 'Filter' }).click()
        await page.getByLabel('Status').selectOption('overdue')
        await page.getByRole('button', { name: 'Apply filters' }).click()
    },
    'sorting': (page) => page.getByRole('button', { name: 'Total' }).click(),
    'switching tabs': (page) => page.locator('.fi-tabs-item', { hasText: 'Pending' }).click(),
}

for (const [name, update] of Object.entries(updates)) {
    test(`${updateSkeletons ? 'shows' : 'does not show'} skeleton rows while ${name}`, async ({ page }) => {
        await openInvoices(page)

        expect(await duringUpdate(page, () => update(page))).toBe(updateSkeletons)
    })
}

test('loads a table that is not deferred without skeleton rows', async ({ page }) => {
    await page.goto('/admin/invoices')

    await expect(page.getByText('INV-2001', { exact: true })).toBeVisible()
    await expect(page.locator('.fi-ta-table-loading-ctn, .fsl-deferred-table')).toHaveCount(0)
    await expect(content(page)).not.toHaveClass(/fi-ta-content-loading/)
})

test('keeps the deferred skeleton on the first load of a deferred table', async ({ page }) => {
    await page.goto('/admin/orders')

    await expect(page.locator('.fsl-deferred-table')).toBeVisible()
    await expect(page.getByText('ORD-1001', { exact: true })).toBeVisible({ timeout: 20000 })
})

for (const colorScheme of ['light', 'dark']) {
    test(`styles the skeleton rows in ${colorScheme} mode`, async ({ page }) => {
        test.skip(! updateSkeletons, 'Only with update skeletons.')

        await page.emulateMedia({ colorScheme })
        await openInvoices(page)

        const style = page.waitForFunction(() => {
            const cell = document.querySelector('.fi-ta-content-loading .fi-ta-text')

            if (! cell) {
                return null
            }

            const bone = getComputedStyle(cell, '::after')

            return { background: bone.backgroundColor, animation: bone.animationName }
        }, null, { timeout: 5000 })

        await page.getByRole('button', { name: 'Next' }).click()
        const { background, animation } = await (await style).jsonValue()

        // The package's bone colours, not Filament's gray-200 / white 10 %.
        expect(background).toBe(colorScheme === 'dark' ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.07)')
        expect(animation).toBe(shimmer ? 'pulse' : 'none')
    })
}
